<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\PostmanParserService;

class CollectionController extends Controller
{
    public function __construct(private PostmanParserService $parser) {}

    /**
     * List all uploaded collections (stored as JSON in storage).
     */
    public function index(): JsonResponse
    {
        $files = Storage::disk('local')->files('collections');

        $collections = collect($files)
            ->map(fn($file) => json_decode(Storage::disk('local')->get($file), true))
            ->filter()
            ->map(fn($c) => [
                'id'           => $c['id'],
                'name'         => $c['name'],
                'description'  => $c['description'] ?? null,
                'total'        => count($c['endpoints'] ?? []),
                'created_at'   => $c['created_at'],
                'updated_at'   => $c['updated_at'],
            ])
            ->values();

        return response()->json(['data' => $collections]);
    }

    /**
     * Upload & parse a Postman collection JSON file.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            // Allow any uploaded file type and rely on JSON decoding to validate structure.
            // Keep the size limit (in kilobytes) to prevent excessively large uploads.
            'file' => 'required|file|max:30240',
            'name' => 'nullable|string|max:120',
        ]);

        $raw  = json_decode($request->file('file')->get(), true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($raw['info'], $raw['item'])) {
            return response()->json(['error' => 'Invalid Postman collection JSON.'], 422);
        }

        $id        = (string) Str::uuid();
        $endpoints = $this->parser->parse($raw['item']);

        $collection = [
            'id'          => $id,
            'name'        => $request->input('name') ?? $raw['info']['name'] ?? 'Untitled Collection',
            'description' => $raw['info']['description'] ?? null,
            'version'     => $raw['info']['version'] ?? '1.0.0',
            'schema'      => $raw['info']['schema'] ?? null,
            'endpoints'   => $endpoints,
            'created_at'  => now()->toIso8601String(),
            'updated_at'  => now()->toIso8601String(),
        ];

        Storage::disk('local')->put("collections/{$id}.json", json_encode($collection, JSON_PRETTY_PRINT));

        return response()->json([
            'data'    => array_merge($collection, ['total' => count($endpoints)]),
            'message' => 'Collection imported successfully.',
        ], 201);
    }

    /**
     * Show a single collection with all its endpoints.
     */
    public function show(string $id): JsonResponse
    {
        $collection = $this->findOrFail($id);

        return response()->json(['data' => $collection]);
    }

    /**
     * Update collection metadata (name / description).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name'        => 'nullable|string|max:120',
            'description' => 'nullable|string',
        ]);

        $collection = $this->findOrFail($id);

        if ($request->filled('name'))        $collection['name']        = $request->input('name');
        if ($request->filled('description')) $collection['description'] = $request->input('description');

        $collection['updated_at'] = now()->toIso8601String();

        Storage::disk('local')->put("collections/{$id}.json", json_encode($collection, JSON_PRETTY_PRINT));

        return response()->json(['data' => $collection, 'message' => 'Collection updated.']);
    }

    /**
     * Delete a collection.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->findOrFail($id);

        Storage::disk('local')->delete("collections/{$id}.json");

        return response()->json(['message' => 'Collection deleted.']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function findOrFail(string $id): array
    {
        $path = "collections/{$id}.json";

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Collection not found.');
        }

        return json_decode(Storage::disk('local')->get($path), true);
    }
}
