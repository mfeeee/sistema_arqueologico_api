<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\BemMaterial\StoreBemMaterialRequest;
use App\Models\BemMaterial;
use Illuminate\Http\JsonResponse;

class BemMaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BemMaterial::scopePublicados(BemMaterial::query())
            ->with(['midias', 'responsavel']);

        if ($request->filled('uf')) {
            $query->where('uf', strtoupper($request->uf));
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        return response()->json($query->paginate(20));
    }

    public function nearby(Request $request): JsonResponse
    {
        $request->validate([
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'raio_km'   => ['nullable', 'numeric', 'min:0.1', 'max:100'],
        ]);

        $bens = BemMaterial::proximo(
            lat: (float) $request->latitude,
            lng: (float) $request->longitude,
            raioKm: (float) ($request->raio_km ?? 5),
        )
            ->where('publicado', true)
            ->with(['midias', 'responsavel'])
            ->limit(50)
            ->get();

        return response()->json($bens);
    }

    public function store(StoreBemMaterialRequest $request): JsonResponse
    {
        $bem = BemMaterial::create($request->validated());

        return response()->json($bem, 201);
    }

    public function show(Request $request, BemMaterial $bemMaterial): JsonResponse
    {
        $this->authorize('view', $bemMaterial);

        return response()->json(
            $bemMaterial->load(['midias', 'responsavel', 'curadorias'])
        );
    }

    public function update(StoreBemMaterialRequest $request, BemMaterial $bemMaterial): JsonResponse
    {
        $this->authorize('update', $bemMaterial);

        $bemMaterial->update($request->validated());

        return response()->json($bemMaterial);
    }

    public function destroy(Request $request, BemMaterial $bemMaterial): JsonResponse
    {
        $this->authorize('delete', $bemMaterial);

        $bemMaterial->update(['deletado_em' => now()]);

        return response()->json(null, 204);
    }
}
