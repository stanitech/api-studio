<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EndpointController extends Controller
{
    /**
     * List all endpoints in a collection (with optional group filter).
     */
    public function index(Request $request, string $collectionId): JsonResponse
    {
        $collection = $this->loadCollection($collectionId);
        $endpoints  = $collection['endpoints'] ?? [];

        if ($group = $request->query('group')) {
            $endpoints = array_filter($endpoints, fn($e) => isset($e['group']) && strcasecmp($e['group'], $group) === 0);
        }

        if ($method = $request->query('method')) {
            $endpoints = array_filter($endpoints, fn($e) => strcasecmp($e['method'], $method) === 0);
        }

        $search = $request->query('search');
        if ($search) {
            $q         = strtolower($search);
            $endpoints = array_filter($endpoints, function ($e) use ($q) {
                return str_contains(strtolower($e['name'] ?? ''), $q)
                    || str_contains(strtolower($e['url'] ?? ''), $q)
                    || str_contains(strtolower($e['ai_summary'] ?? ''), $q);
            });
        }

        return response()->json(['data' => array_values($endpoints)]);
    }

    /**
     * Create a new endpoint in a collection.
     */
    public function store(Request $request, string $collectionId): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:200',
            'method'      => 'required|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'url'         => 'required|string|max:2000',
            'group'       => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'headers'     => 'nullable|array',
            'query'       => 'nullable|array',
            'path_vars'   => 'nullable|array',
            'body'        => 'nullable|array',
            'responses'   => 'nullable|array',
            'auth'        => 'nullable|array',
        ]);

        $collection = $this->loadCollection($collectionId);

        $endpoint = [
            'id'          => (string) Str::uuid(),
            'name'        => $request->input('name'),
            'method'      => strtoupper($request->input('method')),
            'url'         => $request->input('url'),
            'group'       => $request->input('group', 'General'),
            'breadcrumb'  => $request->input('breadcrumb', []),
            'description' => $request->input('description'),
            'headers'     => $request->input('headers', []),
            'query'       => $request->input('query', []),
            'path_vars'   => $request->input('path_vars', []),
            'body'        => $request->input('body', []),
            'responses'   => $request->input('responses', []),
            'auth'        => $request->input('auth', []),
            'ai_summary'  => null,
            'created_at'  => now()->toIso8601String(),
            'updated_at'  => now()->toIso8601String(),
        ];

        $collection['endpoints'][]  = $endpoint;
        $collection['updated_at']   = now()->toIso8601String();

        $this->saveCollection($collectionId, $collection);

        return response()->json(['data' => $endpoint, 'message' => 'Endpoint created.'], 201);
    }

    /**
     * Show a single endpoint.
     */
    public function show(string $collectionId, string $id): JsonResponse
    {
        [$collection, $endpoint] = $this->findEndpoint($collectionId, $id);

        return response()->json(['data' => $endpoint]);
    }

    /**
     * Update an endpoint (full or partial).
     */
    public function update(Request $request, string $collectionId, string $id): JsonResponse
    {
        $request->validate([
            'name'        => 'nullable|string|max:200',
            'method'      => 'nullable|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'url'         => 'nullable|string|max:2000',
            'group'       => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'headers'     => 'nullable|array',
            'query'       => 'nullable|array',
            'path_vars'   => 'nullable|array',
            'body'        => 'nullable|array',
            'responses'   => 'nullable|array',
            'auth'        => 'nullable|array',
            'ai_summary'  => 'nullable|string',
        ]);

        [$collection, $endpoint, $index] = $this->findEndpoint($collectionId, $id, true);

        $fillable = ['name','method','url','group','description','headers','query','path_vars','body','responses','auth','ai_summary'];
        foreach ($fillable as $field) {
            if ($request->has($field)) {
                $endpoint[$field] = $field === 'method'
                    ? strtoupper($request->input($field))
                    : $request->input($field);
            }
        }

        $endpoint['updated_at']           = now()->toIso8601String();
        $collection['endpoints'][$index]  = $endpoint;
        $collection['updated_at']         = now()->toIso8601String();

        $this->saveCollection($collectionId, $collection);

        return response()->json(['data' => $endpoint, 'message' => 'Endpoint updated.']);
    }

    /**
     * Delete an endpoint.
     */
    public function destroy(string $collectionId, string $id): JsonResponse
    {
        [$collection, , $index] = $this->findEndpoint($collectionId, $id, true);

        array_splice($collection['endpoints'], $index, 1);
        $collection['updated_at'] = now()->toIso8601String();

        $this->saveCollection($collectionId, $collection);

        return response()->json(['message' => 'Endpoint deleted.']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function loadCollection(string $id): array
    {
        $path = "collections/{$id}.json";

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Collection not found.');
        }

        return json_decode(Storage::disk('local')->get($path), true);
    }

    private function saveCollection(string $id, array $collection): void
    {
        Storage::disk('local')->put("collections/{$id}.json", json_encode($collection, JSON_PRETTY_PRINT));
    }

    private function findEndpoint(string $collectionId, string $endpointId, bool $returnIndex = false): array
    {
        $collection = $this->loadCollection($collectionId);

        foreach ($collection['endpoints'] ?? [] as $i => $ep) {
            if (($ep['id'] ?? '') === $endpointId) {
                return $returnIndex
                    ? [$collection, $ep, $i]
                    : [$collection, $ep];
            }
        }

        abort(404, 'Endpoint not found.');
    }
}
