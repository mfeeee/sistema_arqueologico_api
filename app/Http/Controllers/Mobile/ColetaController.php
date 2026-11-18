<?php

namespace App\Http\Controllers\Mobile;

use App\Actions\RegistrarColetaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coleta\StoreColetaRequest;
use App\Http\Resources\ColetaResource;
use App\Models\Coleta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ColetaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Coleta::class);

        $coletas = Coleta::where('usuario_id', $request->user()->id)
            ->with(['localizacao', 'artefatoTipos.artefatoTipo', 'midias'])
            ->orderByDesc('data_coleta')
            ->paginate(20);

        return ColetaResource::collection($coletas);
    }

    public function store(StoreColetaRequest $request, RegistrarColetaAction $registrar): ColetaResource
    {
        $dados = $request->validated() + ['id' => (string) Str::uuid(), 'versao' => 1];
        $coleta = $registrar->execute($request->user(), $dados);

        return new ColetaResource(
            $coleta->load(['artefatoTipos.artefatoTipo', 'localizacao'])
        );
    }

    public function show(Coleta $coleta): ColetaResource
    {
        $this->authorize('view', $coleta);

        return new ColetaResource(
            $coleta->load(['localizacao', 'artefatoTipos.artefatoTipo', 'midias'])
        );
    }

    public function update(StoreColetaRequest $request, Coleta $coleta, RegistrarColetaAction $registrar): ColetaResource
    {
        $this->authorize('update', $coleta);

        $validados = $request->validated();
        $dados = array_merge($validados, [
            'id' => $coleta->id,
            'versao' => max((int) ($validados['versao'] ?? 0), $coleta->versao + 1),
        ]);
        $coleta = $registrar->execute($request->user(), $dados);

        return new ColetaResource(
            $coleta->load(['localizacao', 'artefatoTipos.artefatoTipo', 'midias'])
        );
    }

    public function destroy(Coleta $coleta): JsonResponse
    {
        $this->authorize('delete', $coleta);

        $coleta->delete();

        return response()->json(null, 204);
    }
}
