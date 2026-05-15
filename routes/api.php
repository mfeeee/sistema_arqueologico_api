<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CuradoriaController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Mobile\BemMaterialController;
use App\Http\Controllers\Mobile\ColetaController;
use App\Http\Controllers\Mobile\SincronizacaoController;
use Illuminate\Support\Facades\Route;

// -- Auth (Publico)

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// -- Mobile
Route::prefix('v1/mobile')->middleware('auth:sanctum')->group(function () {
    // Coletas
    Route::apiResource('coletas', ColetaController::class);

    // Sincronização batch (mobile offline)
    Route::post('sync', [SincronizacaoController::class, 'sincronizar']);

    // Bens Materiais
    Route::get('bens-materiais/nearby', [BemMaterialController::class, 'nearby']);
    Route::apiResource('bens-materiais', BemMaterialController::class);
});

// -- Admin/Site
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'role:admin,curador'])->group(function () {
    // Curadorias
    Route::get('curadorias', [CuradoriaController::class, 'index']);
    Route::patch('curadorias/{curadoria}/avaliar', [CuradoriaController::class, 'avaliar']);

    // Auditorias (admin)
    Route::get('auditorias', [AuditoriaController::class, 'index']);
    Route::get('auditorias/{auditoria}', [AuditoriaController::class, 'show']);
});
