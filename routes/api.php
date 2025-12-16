<?php

use App\Http\Controllers\Api\IncidentController;
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
