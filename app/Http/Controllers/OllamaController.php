<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Services\OllamaService;

class OllamaController extends Controller
{
    public function __construct(private OllamaService $ollama) {}

    /**
     * Check Ollama server status and model availability.
     */
    public function status(): JsonResponse
    {
        $status = $this->ollama->ping();

        return response()->json([
            'online'         => $status['online'],
            'version'        => $status['version'] ?? null,
            'default_model'  => config('ollama.model'),
            'message'        => $status['online'] ? 'Ollama is running.' : 'Cannot reach Ollama server.',
        ]);
    }

    /**
     * List available local Ollama models.
     */
    public function models(): JsonResponse
    {
        $models = $this->ollama->listModels();

        return response()->json(['data' => $models]);
    }

    /**
     * Generate an AI summary for a single endpoint (SSE streaming).
     *
     * POST /api/ai/summarize
     * Body: { endpoint: {...}, model?: string, collection_id?: string }
     */
    public function summarize(Request $request)
    {
        $request->validate([
            'endpoint'         => 'required|array',
            'endpoint.name'    => 'required|string',
            'endpoint.method'  => 'required|string',
            'endpoint.url'     => 'required|string',
            'model'            => 'nullable|string',
            'collection_id'    => 'nullable|string',
            'endpoint_id'      => 'nullable|string',
        ]);

        $endpoint = $request->input('endpoint');
        $model    = $request->input('model', config('ollama.model'));

        $prompt   = $this->buildPrompt($endpoint);

        // Return Server-Sent Events stream
        return response()->stream(function () use ($prompt, $model, $endpoint, $request) {
            $fullText = '';

            $this->ollama->streamGenerate($prompt, $model, function (string $chunk) use (&$fullText) {
                $fullText .= $chunk;

                // SSE format
                echo "data: " . json_encode(['token' => $chunk]) . "\n\n";
                ob_flush();
                flush();
            });

            // Persist summary back to collection if IDs provided
            $collectionId = $request->input('collection_id');
            $endpointId   = $request->input('endpoint_id');

            if ($collectionId && $endpointId && $fullText) {
                $this->persistSummary($collectionId, $endpointId, trim($fullText));
            }

            echo "data: " . json_encode(['done' => true, 'summary' => trim($fullText)]) . "\n\n";
            ob_flush();
            flush();

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    /**
     * Batch summarize all endpoints in a collection.
     * Streams progress events as each endpoint is processed.
     *
     * POST /api/ai/summarize-collection
     * Body: { collection_id: string, model?: string, force?: bool }
     */
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

        $path = "collections/{$collectionId}.json";

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
                // Skip already summarised endpoints unless force=true
                if (!$force && !empty($endpoint['ai_summary'])) {
                    echo "data: " . json_encode([
                        'type'    => 'skip',
                        'index'   => $i,
                        'id'      => $endpoint['id'],
                        'name'    => $endpoint['name'],
                        'summary' => $endpoint['ai_summary'],
                    ]) . "\n\n";
                    ob_flush(); flush();
                    continue;
                }

                echo "data: " . json_encode([
                    'type'  => 'processing',
                    'index' => $i,
                    'id'    => $endpoint['id'],
                    'name'  => $endpoint['name'],
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
                    'type'    => 'done',
                    'index'   => $i,
                    'id'      => $endpoint['id'],
                    'name'    => $endpoint['name'],
                    'summary' => $endpoint['ai_summary'],
                ]) . "\n\n";
                ob_flush(); flush();
            }

            // Save updated collection
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

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Build a rich prompt that mimics Postman AI documentation style.
     */
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
            if (!($q['disabled'] ?? false)) {
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
        if ($mode === 'raw' && !empty($body['raw'])) {
            $language = $body['options']['raw']['language'] ?? 'json';
            $bodyInfo = "Raw body ({$language}):\n{$body['raw']}";
        } elseif (in_array($mode, ['formdata', 'urlencoded'])) {
            $params = $body[$mode] ?? [];
            foreach ($params as $p) {
                if (!(isset($p['disabled']) ? $p['disabled'] : false)) {
                    $bodyInfo .= "  - {$p['key']} (" . (isset($p['type']) ? $p['type'] : 'text') . "): {$p['value']} — " . (isset($p['description']) ? $p['description'] : '') . "\n";
                }
            }
        }

        // Example response
        $exampleResp = '';
        $responses   = $ep['responses'] ?? [];
        if (!empty($responses[0]['body'])) {
            $decoded = json_decode($responses[0]['body'], true);
            $preview = $decoded ? json_encode($decoded, JSON_PRETTY_PRINT) : $responses[0]['body'];
            $exampleResp = substr($preview, 0, 600) . (strlen($preview) > 600 ? '...' : '');
        }

        // Auth
        $auth     = $ep['auth'] ?? [];
        $authType = $auth['type'] ?? 'none';

        // Prepare conditional parts for heredoc
        $queryPart = $queryParams ? "Query Parameters:\n{$queryParams}" : '';
        $pathPart = $pathVars ? "Path Variables:\n{$pathVars}" : '';
        $bodyPart = $bodyInfo ? "Request Body ({$mode}):\n{$bodyInfo}" : '';
        $examplePart = $exampleResp ? "Example Response:\n{$exampleResp}" : '';
        $descPart = $description ? "Existing Description:\n{$description}" : '';

        return <<<PROMPT
You are an expert API documentation writer, similar to Postman's AI docs feature.

Generate a clear, concise, developer-friendly documentation summary for the following API endpoint. Write in plain English. Follow this structure exactly:

1. **Overview** — One sentence describing what this endpoint does.
2. **Use Case** — When and why a developer would call this endpoint (1-2 sentences).
3. **Authentication** — Describe the auth requirement.
4. **Parameters** — Briefly describe each parameter's purpose (skip if none).
5. **Request Body** — Describe the body fields and their role (skip if none).
6. **Response** — What the API returns on success and what key fields mean (skip if no example).
7. **Notes** — Any important caveats, rate limits, or tips (optional).

Keep the tone technical but approachable. Be specific — don't write generic filler. Do NOT include code examples.

---
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

    /**
     * Persist the AI summary back into the stored collection JSON.
     */
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
