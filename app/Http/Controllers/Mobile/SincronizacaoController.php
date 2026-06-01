<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sincronizacao\SincronizarColetasRequest;
use App\Jobs\ProcessarSincronizacao;
use Illuminate\Http\JsonResponse;

class SincronizacaoController extends Controller
{
    public function sincronizar(SincronizarColetasRequest $request): JsonResponse
    {
        $usuarioId = $request->user()->id;
        $coletas = $request->validated()['coletas'];

        dispatch(new ProcessarSincronizacao($usuarioId, $coletas));

        return response()->json([
            'message' => __('success.synced'),
            'total_itens' => count($coletas),
        ], 202);
    }
}
