<?php

namespace App\Http\Controllers;

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
            ->whereNull('deletado_em')
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
            'artefatos' => $validated['artefatos'] ?? [],
            'versao' => $validated['versao'] ?? 1,
            'dados_coletados' => $validated['dados_coletados'] ?? [],
        ]);

        return response()->json($coleta, 201);
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

        $coleta->update(['deletado_em' => now()]);

        return response()->json(null, 204);
    }
}
