<?php

use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});

// Incident routes
Route::prefix('incidents')->group(function () {
    Route::get('/', [IncidentController::class, 'index']);
    Route::get('/stats', [IncidentController::class, 'stats']);
    Route::post('/', [IncidentController::class, 'store']);
    Route::get('/{id}', [IncidentController::class, 'show']);
    Route::put('/{id}', [IncidentController::class, 'update']);
    Route::delete('/{id}', [IncidentController::class, 'destroy']);
});

// User management routes (SCIM proxy)
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});
