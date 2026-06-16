<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PapelResponsavelBem;
use App\Http\Controllers\Controller;
use App\Models\BemMaterial;
use App\Models\BemResponsavel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BemResponsavelController extends Controller
{
    public function store(Request $request, BemMaterial $bemMaterial): JsonResponse
    {
        $this->authorize('update', $bemMaterial);

        $papelValues = implode(',', array_column(PapelResponsavelBem::cases(), 'value'));

        $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'papel' => ['required', 'string', "in:{$papelValues}"],
        ]);

        $responsavel = BemResponsavel::firstOrCreate(
            [
                'bem_material_id' => $bemMaterial->id,
                'user_id' => $request->user_id,
            ],
            ['papel' => $request->papel]
        );

        if (! $responsavel->wasRecentlyCreated && $responsavel->papel->value !== $request->papel) {
            $responsavel->update(['papel' => $request->papel]);
        }

        return response()->json($bemMaterial->fresh(['responsaveis.usuario', 'curadorResponsavel']));
    }

    public function destroy(Request $request, BemMaterial $bemMaterial, BemResponsavel $bemResponsavel): JsonResponse
    {
        $this->authorize('update', $bemMaterial);

        $bemResponsavel->delete();

        return response()->json($bemMaterial->fresh(['responsaveis.usuario', 'curadorResponsavel']));
    }
}
