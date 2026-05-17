<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\BemMaterial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BemMaterialController extends Controller
{
    public function destroy(Request $request, BemMaterial $bemMaterial): JsonResponse
    {
        $this->authorize('delete', $bemMaterial);

        $bemMaterial->update(['deletado_em' => now()]);

        return response()->json(null, 204);
    }

    public function publicar(Request $request, BemMaterial $bemMaterial): JsonResponse
    {
        $this->authorize('update', $bemMaterial);

        $request->validate([
            'publicado' => ['required', 'boolean'],
        ]);

        $anterior = (bool) $bemMaterial->publicado;
        $novo = (bool) $request->input('publicado');

        BemMaterial::withoutEvents(fn () => $bemMaterial->update(['publicado' => $novo]));

        Auditoria::create([
            'usuario_id'    => $request->user()->id,
            'entidade_tipo' => BemMaterial::class,
            'entidade_id'   => $bemMaterial->id,
            'curadoria_id'  => null,
            'operacao'      => 'Alteração',
            'meio'          => 'Manual',
            'data_hora'     => now(),
            'valor_anterior' => ['publicado' => $anterior],
            'valor_novo'     => ['publicado' => $novo],
        ]);

        return response()->json($bemMaterial->fresh(['midias', 'responsavel']));
    }
}
