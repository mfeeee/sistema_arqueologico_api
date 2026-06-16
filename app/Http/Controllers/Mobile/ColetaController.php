<?php

namespace App\Http\Controllers\Mobile;

use App\Enums\ArtefatoBem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coleta\StoreColetaRequest;
use App\Models\ArtefatoTipo;
use App\Models\Coleta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ColetaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Coleta::class);

        $coletas = Coleta::where('usuario_id', $request->user()->id)
            ->with(['localizacao', 'artefatoTipos.artefatoTipo', 'midias'])
            ->orderByDesc('data_coleta')
            ->paginate(20);

        return response()->json($coletas);
    }

    public function store(StoreColetaRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $localizacaoId = null;
        $lat = $validated['latitude'] ?? null;
        $lng = $validated['longitude'] ?? null;

        if (!empty($validated['latitude']) && !empty($validated['longitude'])) {
            $localizacao = \App\Models\Localizacao::create([
                'cep'        => $validated['cep'] ?? null,
                'logradouro' => $validated['logradouro'] ?? null,
                'municipio'  => $validated['municipio'] ?? null,
                'uf'         => $validated['uf'] ?? null,
            ]);

            DB::statement(
                'UPDATE localizacoes SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
                [$lng, $lat, $localizacao->id]
            );
            
            $localizacaoId = $localizacao->id;
        }

        $coleta = Coleta::create([
            'usuario_id' => $request->user()->id,
            'data_coleta' => $validated['data_coleta'],
            'nome_bem' => $validated['nome_bem'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'natureza_bem' => $validated['natureza'] ?? null,
            'tipo_bem' => $validated['tipo'] ?? null,
            'uf' => $validated['uf'] ?? null,
            'versao' => $validated['versao'] ?? 1,
            'dados_coletados' => $validated['dados_coletados'] ?? [],
            'localizacao_id' => $localizacaoId,
        ]);

        if (! empty($validated['artefatos'])) {
            foreach ($validated['artefatos'] as $valor) {
                try {
                    $enum = ArtefatoBem::from($valor);
                    $nome = $enum->label();

                    $tipo = ArtefatoTipo::where('nome', $nome)->first();
                    if ($tipo) {
                        $coleta->artefatoTipos()->create([
                            'artefato_tipo_id' => $tipo->id,
                        ]);
                    }
                } catch (\ValueError $e) {
                    // Ignora valores inválidos que passaram pela validação (improvável)
                }
            }
        }

        return response()->json(
            $coleta->load(['artefatoTipos.artefatoTipo', 'localizacao']), 201
        );
    }

    public function show(Coleta $coleta): JsonResponse
    {
        $this->authorize('view', $coleta);

        return response()->json(
            $coleta->load(['localizacao', 'artefatoTipos.artefatoTipo', 'midias'])
        );
    }

    public function update(StoreColetaRequest $request, Coleta $coleta): JsonResponse
    {
        $this->authorize('update', $coleta);

        $coleta->update($request->validated());

        return response()->json($coleta);
    }

    public function destroy(Coleta $coleta): JsonResponse
    {
        $this->authorize('delete', $coleta);

        $coleta->delete();

        return response()->json(null, 204);
    }
}
