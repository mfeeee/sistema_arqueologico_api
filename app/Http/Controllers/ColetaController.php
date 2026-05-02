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
        $coleta = Coleta::create([
            ...$request->validated(),
            'usuario_id' => $request->user()->id,
            'status_sincronizacao' => 'sincronizado',
        ]);

        return response()->json($coleta, 201);
    }

    public function show(Request $request, Coleta $coleta): JsonResponse
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

    public function destroy(Request $request, Coleta $coleta): JsonResponse
    {
        $this->authorize('delete', $coleta);

        $coleta->update(['deletado_em' => now()]);

        return response()->json(null, 204);
    }
}
