<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\SavedResponse;

class SavedResponseController extends Controller
{
    /** GET /api/saved-responses */
    public function index(Request $request): JsonResponse
    {
        $query = SavedResponse::where('user_id', Auth::id())
            ->orderByDesc('created_at');

        if ($colId = $request->query('collection_id')) {
            $query->where('collection_id', $colId);
        }
        if ($epId = $request->query('endpoint_id')) {
            $query->where('endpoint_id', $epId);
        }

        return response()->json(['data' => $query->get()]);
    }

    /** POST /api/saved-responses */
    public function store(Request $request): JsonResponse
    {
        if (!Auth::user()->hasPermission('run')) {
            return response()->json(['error' => 'No permission to save responses.'], 403);
        }



        $request->validate([
            'collection_id'    => 'required|string',
            'endpoint_id'      => 'required|string',
            'endpoint_name'    => 'required|string',
            'method'           => 'required|string',
            'url'              => 'required|string',
            'status_code'      => 'required|integer',
            'response_body'    => 'nullable|string',
            'request_headers'  => 'nullable|array',
            'request_body'     => 'nullable|string',
            'response_time_ms' => 'nullable|integer',
            'label'            => 'nullable|string|max:120',
        ]);

        $saved = SavedResponse::create([
            'user_id'          => Auth::id(),
            'collection_id'    => $request->collection_id,
            'endpoint_id'      => $request->endpoint_id,
            'endpoint_name'    => $request->endpoint_name,
            'method'           => $request->method,
            'url'              => $request->url,
            'status_code'      => $request->status_code,
            'response_body'    => $request->response_body,
            'request_headers'  => $request->request_headers,
            'request_body'     => $request->request_body,
            'response_time_ms' => $request->response_time_ms,
            'label'            => $request->label,
        ]);

        return response()->json(['data' => $saved, 'message' => 'Response saved.'], 201);
    }

    /** DELETE /api/saved-responses/{id} */
    public function destroy(int $id): JsonResponse
    {
        $saved = SavedResponse::where('user_id', Auth::id())->findOrFail($id);
        $saved->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
