<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BemMaterialController;
use App\Http\Controllers\ColetaController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    // Coletas
    Route::apiResource('coletas', ColetaController::class);

    // Bens Materiais
    Route::get('bens-materiais/nearby', [BemMaterialController::class, 'nearby']);
    Route::apiResource('bens-materiais', BemMaterialController::class);

});
