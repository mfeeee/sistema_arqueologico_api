<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PapelResponsavelBem;
use App\Http\Controllers\Controller;
use App\Models\BemMaterial;
use App\Models\BemResponsavel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BemResponsavelController extends Controller
{
    public function store(Request $request, BemMaterial $bemMaterial): JsonResponse
    {
        $this->authorize('update', $bemMaterial);

        $papelValues = implode(',', array_column(PapelResponsavelBem::cases(), 'value'));

        $request->validate([
            'user_id' => ['required', 'uuid', Rule::exists('users', 'id')->whereNull('deleted_at')->where('ativo', true)],
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

        if ($bemResponsavel->bem_material_id !== $bemMaterial->id) {
            abort(404);
        }

        $bemResponsavel->delete();

        return response()->json($bemMaterial->fresh(['responsaveis.usuario', 'curadorResponsavel']));
    }
}
