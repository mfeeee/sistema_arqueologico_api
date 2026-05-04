<?php

namespace App\Http\Controllers;

use App\Enums\AcaoResultanteCuradoria;
use App\Enums\StatusColeta;
use App\Http\Requests\Curadoria\AvaliarCuradoriaRequest;
use App\Models\BemMaterial;
use App\Models\Curadoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuradoriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Curadoria::class);

        $curadorias = Curadoria::with(['coleta', 'bemMaterial', 'curador'])
            ->where('status', 'pendente')
            ->orderBy('created_at')
            ->paginate(20);

        return response()->json($curadorias);
    }

    public function avaliar(AvaliarCuradoriaRequest $request, Curadoria $curadoria): JsonResponse
    {
        $this->authorize('avaliar', $curadoria);

        DB::transaction(function () use ($request, $curadoria) {
            $acao = AcaoResultanteCuradoria::from($request->acao_resultante);

            $bemMaterialId = match ($acao) {
                AcaoResultanteCuradoria::CRIAR_SITIO => $this->criarBemMaterial($curadoria)->id,
                AcaoResultanteCuradoria::ATUALIZAR_SITIO => $request->bem_material_id,
                AcaoResultanteCuradoria::REJEITAR => null,
            };

            $curadoria->update([
                'status' => $request->status,
                'acao_resultante' => $request->acao_resultante,
                'bem_material_id' => $bemMaterialId,
                'data_avaliacao' => now(),
                'observacao' => $request->observacao,
                'usuario_id' => $request->user()->id,
            ]);

            $curadoria->coleta->update([
                'status_sincronizacao' => StatusColeta::SINCRONIZADO->value,
            ]);
        });

        return response()->json($curadoria->fresh(['coleta', 'bemMaterial']));
    }

    private function criarBemMaterial(Curadoria $curadoria): BemMaterial
    {
        $coleta = $curadoria->coleta;

        return BemMaterial::create([
            'coleta_id' => $coleta->id,
            'nome_bem' => $coleta->nome_bem,
            'natureza' => $coleta->natureza_bem?->value,
            'tipo' => $coleta->tipo_bem?->value,
            'uf' => $coleta->uf,
            'latitude' => $coleta->latitude,
            'longitude' => $coleta->longitude,
            'artefatos' => $coleta->artefatos,
            'publicado' => false,
        ]);
    }
}
