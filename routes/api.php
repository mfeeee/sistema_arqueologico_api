<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ArtigoBemMaterialController;
use App\Http\Controllers\Admin\ArtigoCientificoController as AdminArtigoCientificoController;
use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\BemMaterialController as AdminBemMaterialController;
use App\Http\Controllers\Admin\BemResponsavelController;
use App\Http\Controllers\Admin\CuradoriaController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Mobile\ArtefatoTipoController;
use App\Http\Controllers\Mobile\ArtigoCientificoController;
use App\Http\Controllers\Mobile\BemMaterialController;
use App\Http\Controllers\Mobile\ColetaController;
use App\Http\Controllers\Mobile\FotoUploadController;
use App\Http\Controllers\Mobile\NotificacaoController;
use App\Http\Controllers\Mobile\PreferenciaNotificacaoController;
use App\Http\Controllers\Mobile\SincronizacaoController;
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
        Route::get('/me', [ProfileController::class, 'show']);
        Route::patch('/me', [ProfileController::class, 'update']);
        Route::post('/me/avatar', [ProfileController::class, 'uploadAvatar']);
        Route::delete('/me/avatar', [ProfileController::class, 'deleteAvatar']);
        Route::delete('/conta', [ProfileController::class, 'destroy']);
        // Token FCM
        Route::post('notificacoes/token', [NotificacaoController::class, 'vincularToken']);
    });
});

// -- Mobile
Route::prefix('v1/mobile')->group(function () {
    // Leitura pública — token opcional, validado se enviado; 30 req/min (guest) ou 120 (autenticado)
    Route::middleware(['auth.optional:sanctum', 'throttle:public-api'])->group(function () {
        Route::get('bens-materiais/nearby', [BemMaterialController::class, 'nearby']);
        Route::get('bens-materiais', [BemMaterialController::class, 'index']);
        Route::get('bens-materiais/{bemMaterial}', [BemMaterialController::class, 'show']);
        Route::get('bens-materiais/{bemMaterial}/artigos', [ArtigoCientificoController::class, 'porBemMaterial']);
        Route::get('bens-materiais/{bemMaterial}/colaboradores', [CuradoriaController::class, 'colaboradores']);
    });

    // Autenticação obrigatória
    Route::middleware('auth:sanctum')->group(function () {
        // Tipos de Artefato
        Route::get('artefato-tipos', [ArtefatoTipoController::class, 'index']);

        // Coletas
        Route::apiResource('coletas', ColetaController::class);

        // Sincronização batch (mobile offline)
        Route::post('sync', [SincronizacaoController::class, 'sincronizar']);

        // Fotos
        Route::post('fotos', [FotoUploadController::class, 'store']);

        // Sugerir estudo — passa por curadoria antes de ser publicado
        Route::get('artigos-cientificos/buscar-doi', [ArtigoCientificoController::class, 'buscarPorDoi']);
        Route::post('submissoes-artigos', [SubmissaoArtigoController::class, 'store']);

        // Preferências de notificações
        Route::get('preferencias-notificacoes', [PreferenciaNotificacaoController::class, 'show']);
        Route::put('preferencias-notificacoes', [PreferenciaNotificacaoController::class, 'update']);

        // Notificações
        Route::get('notificacoes', [NotificacaoController::class, 'index']);
        Route::patch('notificacoes/{notificacao}/lida', [NotificacaoController::class, 'marcarComoLida']);
    });
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
    Route::patch('bens-materiais/{bemMaterial}/curador-responsavel', [AdminBemMaterialController::class, 'atualizarCuradorResponsavel']);
    Route::delete('bens-materiais/{bemMaterial}', [AdminBemMaterialController::class, 'destroy']);
    Route::post('bens-materiais/{bemMaterial}/responsaveis', [BemResponsavelController::class, 'store']);
    Route::delete('bens-materiais/{bemMaterial}/responsaveis/{bemResponsavel}', [BemResponsavelController::class, 'destroy']);

    // Usuários (admin)
    Route::get('usuarios', [AdminUserController::class, 'index'])->middleware('role:admin');
    Route::get('usuarios/curadores', [AdminUserController::class, 'curadores']);
    Route::patch('usuarios/{user}/perfil', [AdminUserController::class, 'updatePerfil'])->middleware('role:admin');

    // Auditorias (admin)
    Route::get('auditorias', [AuditoriaController::class, 'index']);
    Route::get('auditorias/{auditoria}', [AuditoriaController::class, 'show']);
    Route::post('auditorias/{auditoria}/restaurar', [AuditoriaController::class, 'restaurar']);

    // Artigos científicos (admin)
    Route::get('artigos-cientificos', [AdminArtigoCientificoController::class, 'index']);
    Route::get('artigos-cientificos/{artigo}', [AdminArtigoCientificoController::class, 'show']);
    Route::delete('artigos-cientificos/{artigo}', [AdminArtigoCientificoController::class, 'destroy']);

    // Artigos — remoção de vínculo com bem material
    Route::delete('artigos-bem-material/{artigoBemMaterial}', [ArtigoBemMaterialController::class, 'destroy']);

    // Colaboradores por bem material
    Route::get('bens-materiais/{bemMaterial}/colaboradores', [CuradoriaController::class, 'colaboradores']);
});
