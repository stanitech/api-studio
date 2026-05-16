<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\EndpointController;
use App\Http\Controllers\OllamaController;
use App\Http\Controllers\ProxyController;
use App\Http\Controllers\SavedResponseController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AuthMiddleware;

// ── Auth (public) ─────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login',  [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
});

// ── Protected (login required) ────────────────────────────────────────────────
Route::middleware(AuthMiddleware::class)->group(function () {

    // Collections
    Route::prefix('collections')->group(function () {
        Route::get('/',         [CollectionController::class, 'index']);
        Route::post('/upload',  [CollectionController::class, 'upload']);
        Route::get('/{id}',     [CollectionController::class, 'show']);
        Route::put('/{id}',     [CollectionController::class, 'update']);
        Route::delete('/{id}',  [CollectionController::class, 'destroy']);
    });

    // Endpoints
    Route::prefix('collections/{collectionId}/endpoints')->group(function () {
        Route::get('/',         [EndpointController::class, 'index']);
        Route::post('/',        [EndpointController::class, 'store']);
        Route::get('/{id}',     [EndpointController::class, 'show']);
        Route::put('/{id}',     [EndpointController::class, 'update']);
        Route::delete('/{id}',  [EndpointController::class, 'destroy']);
    });

    // Proxy Runner
    Route::post('/proxy/run',   [ProxyController::class, 'run']);

    // AI
    Route::prefix('ai')->group(function () {
        Route::get('/status',                [OllamaController::class, 'status']);
        Route::get('/models',                [OllamaController::class, 'models']);
        Route::post('/summarize',            [OllamaController::class, 'summarize']);
        Route::post('/summarize-collection', [OllamaController::class, 'summarizeCollection']);
    });

    // Saved Responses
    Route::prefix('saved-responses')->group(function () {
        Route::get('/',         [SavedResponseController::class, 'index']);
        Route::post('/',        [SavedResponseController::class, 'store']);
        Route::delete('/{id}',  [SavedResponseController::class, 'destroy']);
    });

    // User Management
    Route::prefix('users')->group(function () {
        Route::get('/',                 [UserController::class, 'index']);
        Route::post('/',                [UserController::class, 'store']);
        Route::put('/{id}',             [UserController::class, 'update']);
        Route::delete('/{id}',          [UserController::class, 'destroy']);
        Route::post('/change-password', [UserController::class, 'changePassword']);
    });

    // Chat / Real-time Collaboration
    Route::prefix('chat')->group(function () {
        Route::get('/messages',  [ChatController::class, 'index']);
        Route::post('/messages', [ChatController::class, 'store']);
        Route::delete('/messages/{id}', [ChatController::class, 'destroy']);
        Route::get('/poll',      [ChatController::class, 'poll']);
        Route::get('/users',     [ChatController::class, 'users']);
    });
});
