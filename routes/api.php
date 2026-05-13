<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\EndpointController;
use App\Http\Controllers\OllamaController;

/*
|--------------------------------------------------------------------------
| API Routes — Ready API Docs
|--------------------------------------------------------------------------
*/

// Collections (Postman JSON upload & management)
Route::prefix('collections')->group(function () {
    Route::get('/',                        [CollectionController::class, 'index']);
    Route::post('/upload',                 [CollectionController::class, 'upload']);
    Route::get('/{id}',                    [CollectionController::class, 'show']);
    Route::put('/{id}',                    [CollectionController::class, 'update']);
    Route::delete('/{id}',                [CollectionController::class, 'destroy']);
});

// Endpoints (CRUD for individual endpoints within a collection)
Route::prefix('collections/{collectionId}/endpoints')->group(function () {
    Route::get('/',                        [EndpointController::class, 'index']);
    Route::post('/',                       [EndpointController::class, 'store']);
    Route::get('/{id}',                    [EndpointController::class, 'show']);
    Route::put('/{id}',                    [EndpointController::class, 'update']);
    Route::delete('/{id}',                [EndpointController::class, 'destroy']);
});

// Ollama AI Routes
Route::prefix('ai')->group(function () {
    // Stream AI summary for a single endpoint
    Route::post('/summarize',              [OllamaController::class, 'summarize']);

    // Batch summarize all endpoints in a collection
    Route::post('/summarize-collection',   [OllamaController::class, 'summarizeCollection']);

    // Check Ollama connection/model availability
    Route::get('/status',                  [OllamaController::class, 'status']);

    // List available Ollama models
    Route::get('/models',                  [OllamaController::class, 'models']);
});
