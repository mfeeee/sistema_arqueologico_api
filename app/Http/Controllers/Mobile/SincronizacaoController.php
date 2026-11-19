<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sincronizacao\SincronizarColetasRequest;
use App\Jobs\ProcessarSincronizacao;
use App\Models\Coleta;
use App\Models\Curadoria;
use Illuminate\Http\JsonResponse;

class SincronizacaoController extends Controller
{
    public function sincronizar(SincronizarColetasRequest $request): JsonResponse
    {
        $usuario = $request->user();
        $coletas = $request->validated()['coletas'];
        $ids = array_column($coletas, 'id');

        // Checagem rápida antes da fila: o app recebe 403/409/422 na hora (a fila continua assíncrona, RNF02).
        // O job refaz as mesmas checagens, porque ele é a fonte da verdade.
        $existentes = Coleta::withTrashed()
            ->whereIn('id', $ids)
            ->get(['id', 'usuario_id', 'versao', 'deleted_at'])
            ->keyBy('id');

        // Depois de enviada para curadoria, a coleta não pode mais ser editada.
        $enviadasParaCuradoria = Curadoria::where('entidade_tipo', 'coleta')
            ->whereIn('entidade_id', $ids)
            ->pluck('entidade_id')
            ->flip();

        $aceitas = [];
        $conflitos = [];
        $negadas = [];
        $enviadas = [];

        foreach ($coletas as $coleta) {
            $existente = $existentes->get($coleta['id']);

            if ($existente !== null && $usuario->cannot('update', $existente)) {
                $negadas[] = $coleta['id'];
            } elseif ($existente !== null && ($existente->trashed() || $existente->versao > $coleta['versao'])) {
                $conflitos[] = $coleta['id'];
            } elseif ($enviadasParaCuradoria->has($coleta['id'])) {
                $enviadas[] = $coleta['id'];
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
            $enviadas !== [] => 422,
            default => 403,
        };

        return response()->json([
            'message' => match ($status) {
                202 => __('success.synced'),
                422 => __('errors.coleta_ja_enviada'),
                default => __('errors.sync_recusado'),
            },
            'total_itens' => count($coletas),
            'aceitas' => count($aceitas),
            'conflitos' => $conflitos,
            'negadas' => $negadas,
            'enviadas_para_curadoria' => $enviadas,
        ], $status);
    }
}
