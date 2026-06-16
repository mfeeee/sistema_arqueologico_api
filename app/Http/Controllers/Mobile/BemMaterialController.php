<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\BemMaterialResource;
use App\Models\BemMaterial;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BemMaterialController extends Controller
{
    public function index(Request $request): JsonResponse|Responsable
    {
        $query = BemMaterial::query()
            ->with(['midias', 'responsaveis', 'curadorResponsavel', 'artefatoTipos.artefatoTipo']);

        [$hasPublicadoFilter, $publicadoFilter] = $this->resolvePublicadoFilter($request);

        if ($hasPublicadoFilter) {
            $query->whereNull('deleted_at');

            if (! is_null($publicadoFilter)) {
                $query->where('publicado', $publicadoFilter);
            }
        } else {
            $query->publicados();
        }

        if ($request->filled('uf')) {
            $query->where('uf', strtoupper($request->uf));
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        return BemMaterialResource::collection($query->paginate(20));
    }

    public function nearby(Request $request): JsonResponse|Responsable
    {
        $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'raio_km' => ['nullable', 'numeric', 'min:0.1', 'max:100'],
        ]);

        $bensQuery = BemMaterial::proximo(
            lat: (float) $request->latitude,
            lng: (float) $request->longitude,
            raioKm: (float) ($request->raio_km ?? 5),
        );

        [$hasPublicadoFilter, $publicadoFilter] = $this->resolvePublicadoFilter($request);

        if ($hasPublicadoFilter) {
            $bensQuery->whereNull('deleted_at');

            if (! is_null($publicadoFilter)) {
                $bensQuery->where('publicado', $publicadoFilter);
            }
        } else {
            $bensQuery->where('publicado', true)->whereNull('deleted_at');
        }

        $bens = $bensQuery->with(['midias', 'responsaveis', 'curadorResponsavel', 'artefatoTipos.artefatoTipo'])
            ->limit(50)
            ->get();

        return BemMaterialResource::collection($bens);
    }

    public function show(Request $request, BemMaterial $bemMaterial): JsonResponse|Responsable
    {
        $bemMaterialId = basename($request->path());

        $bemMaterial = BemMaterial::query()
            ->with(['midias', 'responsaveis', 'curadorResponsavel', 'artefatoTipos.artefatoTipo'])
            ->findOrFail($bemMaterialId);

        $this->authorize('view', $bemMaterial);

        return new BemMaterialResource($bemMaterial);
    }

    private function resolvePublicadoFilter(Request $request): array
    {
        $key = null;

        if ($request->has('publicado')) {
            $key = 'publicado';
        } elseif ($request->has('publicados')) {
            $key = 'publicados';
        }

        if (is_null($key)) {
            return [false, true];
        }

        $rawValue = strtolower(trim((string) $request->input($key)));

        if ($rawValue === 'all') {
            return [true, null];
        }

        if (in_array($rawValue, ['1', 'true'], true)) {
            return [true, true];
        }

        if (in_array($rawValue, ['0', 'false'], true)) {
            return [true, false];
        }

        return [false, true];
    }
}
