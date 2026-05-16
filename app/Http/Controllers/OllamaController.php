<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Services\OllamaService;

class OllamaController extends Controller
{
    public function __construct(private OllamaService $ollama) {}

    public function status(): JsonResponse
    {
        $status = $this->ollama->ping();
        return response()->json([
            'online'        => $status['online'],
            'version'       => $status['version'] ?? null,
            'default_model' => config('ollama.model'),
            'message'       => $status['online'] ? 'Ollama is running.' : 'Cannot reach Ollama server.',
        ]);
    }

    public function models(): JsonResponse
    {
        $models = $this->ollama->listModels();
        return response()->json(['data' => $models]);
    }

    public function summarize(Request $request)
    {
        $request->validate([
            'endpoint'        => 'required|array',
            'endpoint.name'   => 'required|string',
            'endpoint.method' => 'required|string',
            'endpoint.url'    => 'required|string',
            'model'           => 'nullable|string',
            'collection_id'   => 'nullable|string',
            'endpoint_id'     => 'nullable|string',
        ]);

        $endpoint = $request->input('endpoint');
        $model    = $request->input('model', config('ollama.model'));
        $prompt   = $this->buildPrompt($endpoint);

        return response()->stream(function () use ($prompt, $model, $request) {
            $fullText = '';

            $this->ollama->streamGenerate($prompt, $model, function (string $chunk) use (&$fullText) {
                $fullText .= $chunk;
                echo "data: " . json_encode(['token' => $chunk]) . "\n\n";
                ob_flush(); flush();
            });

            $collectionId = $request->input('collection_id');
            $endpointId   = $request->input('endpoint_id');
            if ($collectionId && $endpointId && $fullText) {
                $this->persistSummary($collectionId, $endpointId, trim($fullText));
            }

            echo "data: " . json_encode(['done' => true, 'summary' => trim($fullText)]) . "\n\n";
            ob_flush(); flush();

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    public function summarizeCollection(Request $request)
    {
        $request->validate([
            'collection_id' => 'required|string',
            'model'         => 'nullable|string',
            'force'         => 'nullable|boolean',
        ]);

        $collectionId = $request->input('collection_id');
        $model        = $request->input('model', config('ollama.model'));
        $force        = $request->boolean('force', false);
        $path         = "collections/{$collectionId}.json";

        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['error' => 'Collection not found.'], 404);
        }

        $collection = json_decode(Storage::disk('local')->get($path), true);

        return response()->stream(function () use ($collection, $collectionId, $model, $force, $path) {
            $endpoints = $collection['endpoints'] ?? [];
            $total     = count($endpoints);

            echo "data: " . json_encode(['type' => 'start', 'total' => $total]) . "\n\n";
            ob_flush(); flush();

            foreach ($endpoints as $i => &$endpoint) {
                if (!$force && !empty($endpoint['ai_summary'])) {
                    echo "data: " . json_encode([
                        'type' => 'skip', 'index' => $i,
                        'id' => $endpoint['id'], 'name' => $endpoint['name'],
                        'summary' => $endpoint['ai_summary'],
                    ]) . "\n\n";
                    ob_flush(); flush();
                    continue;
                }

                echo "data: " . json_encode([
                    'type' => 'processing', 'index' => $i,
                    'id' => $endpoint['id'], 'name' => $endpoint['name'],
                ]) . "\n\n";
                ob_flush(); flush();

                $prompt  = $this->buildPrompt($endpoint);
                $summary = '';
                $this->ollama->streamGenerate($prompt, $model, function (string $chunk) use (&$summary) {
                    $summary .= $chunk;
                });

                $endpoint['ai_summary'] = trim($summary);
                $endpoint['updated_at'] = now()->toIso8601String();

                echo "data: " . json_encode([
                    'type' => 'done', 'index' => $i,
                    'id' => $endpoint['id'], 'name' => $endpoint['name'],
                    'summary' => $endpoint['ai_summary'],
                ]) . "\n\n";
                ob_flush(); flush();
            }

            $collection['endpoints']  = $endpoints;
            $collection['updated_at'] = now()->toIso8601String();
            Storage::disk('local')->put("collections/{$collectionId}.json", json_encode($collection, JSON_PRETTY_PRINT));

            echo "data: " . json_encode(['type' => 'complete', 'total' => $total]) . "\n\n";
            ob_flush(); flush();

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    // ── Updated buildPrompt (user-provided version) ───────────────────────────

    private function buildPrompt(array $ep): string
    {
        $method      = strtoupper($ep['method'] ?? 'GET');
        $name        = $ep['name'] ?? 'Unnamed Endpoint';
        $url         = $ep['url'] ?? '';
        $description = $ep['description'] ?? '';
        $group       = $ep['group'] ?? '';

        // Query params
        $queryParams = '';
        foreach ($ep['query'] ?? [] as $q) {
            if (! ($q['disabled'] ?? false)) {
                $queryParams .= "  - {$q['key']}: {$q['value']} — {$q['description']}\n";
            }
        }

        // Path variables
        $pathVars = '';
        foreach ($ep['path_vars'] ?? [] as $pv) {
            $pathVars .= "  - :{$pv['key']} = {$pv['value']}\n";
        }

        // Request body
        $bodyInfo = '';
        $body     = $ep['body'] ?? [];
        $mode     = $body['mode'] ?? '';
        if ($mode === 'raw' && ! empty($body['raw'])) {
            $language = $body['options']['raw']['language'] ?? 'json';
            $bodyInfo = "Raw body ({$language}):\n{$body['raw']}";
        } elseif (in_array($mode, ['formdata', 'urlencoded'])) {
            $params = $body[$mode] ?? [];
            foreach ($params as $p) {
                if (! (isset($p['disabled']) ? $p['disabled'] : false)) {
                    $bodyInfo .= "  - {$p['key']} (" . (isset($p['type']) ? $p['type'] : 'text') . "): {$p['value']} — " . (isset($p['description']) ? $p['description'] : '') . "\n";
                }
            }
        }

        // Example response
        $exampleResp = '';
        $responses   = $ep['responses'] ?? [];
        if (! empty($responses[0]['body'])) {
            $decoded     = json_decode($responses[0]['body'], true);
            $preview     = $decoded ? json_encode($decoded, JSON_PRETTY_PRINT) : $responses[0]['body'];
            $exampleResp = substr($preview, 0, 600) . (strlen($preview) > 600 ? '...' : '');
        }

        // Auth
        $auth     = $ep['auth'] ?? [];
        $authType = $auth['type'] ?? 'none';

        // Prepare conditional sections for heredoc
        $queryPart   = $queryParams ? "Query Parameters:\n{$queryParams}" : '';
        $pathPart    = $pathVars    ? "Path Variables:\n{$pathVars}"     : '';
        $bodyPart    = $bodyInfo    ? "Request Body ({$mode}):\n{$bodyInfo}" : '';
        $examplePart = $exampleResp ? "Example Response:\n{$exampleResp}" : '';
        $descPart    = $description ? "Existing Description:\n{$description}" : '';

        return <<<PROMPT
You are an expert API documentation writer, similar to Postman's AI documentation assistant.

Generate clear, concise, developer-friendly documentation for the API endpoint provided.

Use plain English and base your output ONLY on the supplied endpoint data.

Do not invent:
- fields
- behaviors
- authentication methods
- rate limits
- validation rules
- response structures
- business logic
- implementation details

If information is missing, explicitly say so instead of guessing.

Follow this structure exactly:

1. **Overview** — One sentence explaining what the endpoint does.
2. **Use Case** — Briefly explain when and why a developer would use this endpoint.
3. **Authentication** — Describe the authentication requirement only if explicitly provided.
Otherwise state:
"No authentication details provided."
4. **Parameters** — List and briefly explain each parameter and its purpose.
Skip this section entirely if there are no parameters.
5. **Request Body** — List and briefly explain each request body field and its role.
Mention required fields when known.
Skip this section entirely if there is no request body.
6. **Response** — Describe the successful response and explain the meaning of important response fields using ONLY the provided example or schema.
Skip this section entirely if no response example/schema is available.
7. **Notes** — Include important implementation details, constraints, caveats, or developer tips ONLY if explicitly available.
Otherwise omit this section entirely.

Additional Rules:
- Keep the tone technical but approachable.
- Avoid generic filler language.
- Do not include code examples.
- Do not repeat the endpoint URL unless necessary.
- Prefer short paragraphs or bullet points for readability.
- Prioritize describing real field behavior inferred from examples over generic API terminology.
- Be precise and developer-focused.

--------------------------------------------------
API ENDPOINT DETAILS
--------------------------------------------------

Endpoint Name: {$name}
Group / Module: {$group}
HTTP Method: {$method}
URL: {$url}
Auth: {$authType}
{$queryPart}
{$pathPart}
{$bodyPart}
{$examplePart}
{$descPart}
---

Write the documentation summary now:
PROMPT;
    }

    private function persistSummary(string $collectionId, string $endpointId, string $summary): void
    {
        $path = "collections/{$collectionId}.json";
        if (!Storage::disk('local')->exists($path)) return;

        $collection = json_decode(Storage::disk('local')->get($path), true);
        foreach ($collection['endpoints'] as &$ep) {
            if (($ep['id'] ?? '') === $endpointId) {
                $ep['ai_summary'] = $summary;
                $ep['updated_at'] = now()->toIso8601String();
                break;
            }
        }
        $collection['updated_at'] = now()->toIso8601String();
        Storage::disk('local')->put($path, json_encode($collection, JSON_PRETTY_PRINT));
    }
}
