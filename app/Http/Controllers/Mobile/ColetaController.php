<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coleta\StoreColetaRequest;
use App\Models\Coleta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ColetaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Coleta::class);

        $coletas = Coleta::where('usuario_id', $request->user()->id)
            ->orderByDesc('data_coleta')
            ->paginate(20);

        return response()->json($coletas);
    }

    public function store(StoreColetaRequest $request): JsonResponse
    {
        $validated = $request->validated();

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
        ]);

        if (! empty($validated['artefatos'])) {
            foreach ($validated['artefatos'] as $valor) {
                try {
                    $enum = \App\Enums\ArtefatoBem::from($valor);
                    $nome = $enum->label();
                    
                    $tipo = \App\Models\ArtefatoTipo::where('nome', $nome)->first();
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

        return response()->json($coleta->load('artefatoTipos.artefatoTipo'), 201);
    }

    public function show(Coleta $coleta): JsonResponse
    {
        $this->authorize('view', $coleta);

        return response()->json($coleta);
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
