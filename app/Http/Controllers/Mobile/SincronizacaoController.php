<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sincronizacao\SincronizarColetasRequest;
use App\Jobs\ProcessarSincronizacao;
use Illuminate\Http\JsonResponse;

class SincronizacaoController extends Controller
{
    public function sincronizar(SincronizarColetasRequest $request): JsonResponse
    {
        $usuarioId = $request->user()->id;
        $coletas = $request->validated()['coletas'];

        ProcessarSincronizacao::dispatch($usuarioId, $coletas);

        return response()->json([
            'message' => 'Sincronização recebida e enfileirada.',
            'total_itens' => count($coletas),
        ], 202);
    }
}
