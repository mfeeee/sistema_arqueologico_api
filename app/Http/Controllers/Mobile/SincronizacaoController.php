<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sincronizacao\SincronizarColetasRequest;
use App\Jobs\ProcessarSincronizacao;
use App\Models\Coleta;
use Illuminate\Http\JsonResponse;

class SincronizacaoController extends Controller
{
    public function sincronizar(SincronizarColetasRequest $request): JsonResponse
    {
        $usuario = $request->user();
        $coletas = $request->validated()['coletas'];

        // Checagem rápida antes da fila: o app recebe 403/409 na hora (a fila continua assíncrona, RNF02).
        // O job refaz as mesmas checagens, porque ele é a fonte da verdade.
        $existentes = Coleta::withTrashed()
            ->whereIn('id', array_column($coletas, 'id'))
            ->get(['id', 'usuario_id', 'versao', 'deleted_at'])
            ->keyBy('id');

        $aceitas = [];
        $conflitos = [];
        $negadas = [];

        foreach ($coletas as $coleta) {
            $existente = $existentes->get($coleta['id']);

            if ($existente !== null && $usuario->cannot('update', $existente)) {
                $negadas[] = $coleta['id'];
            } elseif ($existente !== null && ($existente->trashed() || $existente->versao > $coleta['versao'])) {
                $conflitos[] = $coleta['id'];
            } else {
                $aceitas[] = $coleta;
            }
        }

        foreach (array_chunk($aceitas, 50) as $lote) {
            dispatch(new ProcessarSincronizacao($usuario->id, $lote));
        }

        $status = match (true) {
            $aceitas !== [] => 202,
            $conflitos !== [] => 409,
            default => 403,
        };

        return response()->json([
            'message' => $status === 202 ? __('success.synced') : __('errors.sync_recusado'),
            'total_itens' => count($coletas),
            'aceitas' => count($aceitas),
            'conflitos' => $conflitos,
            'negadas' => $negadas,
        ], $status);
    }
}
