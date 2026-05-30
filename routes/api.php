<?php

use App\Http\Controllers\Admin\ArtigoBemMaterialController;
use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\BemMaterialController as AdminBemMaterialController;
use App\Http\Controllers\Admin\CuradoriaController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Mobile\ArtigoCientificoController;
use App\Http\Controllers\Mobile\BemMaterialController;
use App\Http\Controllers\Mobile\ColetaController;
use App\Http\Controllers\Mobile\FotoUploadController;
use App\Http\Controllers\Mobile\NotificacaoController;
use App\Http\Controllers\Mobile\SincronizacaoController;
use App\Http\Controllers\Mobile\PreferenciaNotificacaoController;
use App\Http\Controllers\Mobile\SubmissaoArtigoController;
use Illuminate\Support\Facades\Route;

// -- Auth (Publico)

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/password-reset', [PasswordResetController::class, 'solicitar']);
    Route::post('/password-reset/confirm', [PasswordResetController::class, 'confirmar']);

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
    Route::apiResource('bens-materiais', BemMaterialController::class)->only(['index', 'show']);

    // Fotos
    Route::post('fotos', [FotoUploadController::class, 'store']);

    // Artigos científicos — busca por DOI, listagem por bem material e colaboradores
    Route::get('artigos-cientificos/buscar-doi', [ArtigoCientificoController::class, 'buscarPorDoi']);
    Route::get('bens-materiais/{bemMaterial}/artigos', [ArtigoCientificoController::class, 'porBemMaterial']);
    Route::get('bens-materiais/{bemMaterial}/colaboradores', [CuradoriaController::class, 'colaboradores']);

    // Submissão de artigos por usuário autenticado
    Route::post('submissoes-artigos', [SubmissaoArtigoController::class, 'store']);

    // Preferências de notificações
    Route::get('preferencias-notificacoes', [PreferenciaNotificacaoController::class, 'show']);
    Route::put('preferencias-notificacoes', [PreferenciaNotificacaoController::class, 'update']);
    // Notificações
    Route::get('notificacoes', [NotificacaoController::class, 'index']);
    Route::patch('notificacoes/{notificacao}/lida', [NotificacaoController::class, 'marcarComoLida']);
});

// -- Admin/Site
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'role:admin,curador'])->group(function () {
    // Curadorias
    Route::get('curadorias', [CuradoriaController::class, 'index']);
    Route::get('curadorias/{curadoria}', [CuradoriaController::class, 'show']);
    Route::patch('curadorias/{curadoria}/avaliar', [CuradoriaController::class, 'avaliar']);
    Route::get('bens-materiais/{bemMaterial}/curadorias', [CuradoriaController::class, 'porBemMaterial']);

    // Bens Materiais (admin)
    Route::patch('bens-materiais/{bemMaterial}/publicar', [AdminBemMaterialController::class, 'publicar']);
    Route::delete('bens-materiais/{bemMaterial}', [AdminBemMaterialController::class, 'destroy']);

    // Auditorias (admin)
    Route::get('auditorias', [AuditoriaController::class, 'index']);
    Route::get('auditorias/{auditoria}', [AuditoriaController::class, 'show']);

    // Artigos — remoção de vínculo com bem material
    Route::delete('artigos-bem-material/{artigoBemMaterial}', [ArtigoBemMaterialController::class, 'destroy']);

    // Colaboradores por bem material
    Route::get('bens-materiais/{bemMaterial}/colaboradores', [CuradoriaController::class, 'colaboradores']);
});
