<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtigoBemMaterial;
use App\Models\Auditoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArtigoBemMaterialController extends Controller
{
    /**
     * Remove o vínculo entre um artigo e um bem material.
     * O artigo em artigos_cientificos é preservado.
     * DELETE /admin/artigos-bem-material/{id}
     */
    public function destroy(Request $request, ArtigoBemMaterial $artigoBemMaterial): JsonResponse
    {
        $this->authorize('delete', $artigoBemMaterial);

        $artigo = $artigoBemMaterial->artigo;

        $anterior = [
            'artigo_id' => $artigoBemMaterial->artigo_id,
            'bem_material_id' => $artigoBemMaterial->bem_material_id,
            'tipo_mencao' => $artigoBemMaterial->tipo_mencao,
            'trecho_relevante' => $artigoBemMaterial->trecho_relevante,
            'artigo_titulo' => $artigo?->titulo,
            'artigo_autores' => $artigo?->autores,
            'artigo_doi' => $artigo?->doi,
            'artigo_periodico' => $artigo?->periodico,
            'artigo_ano_publicacao' => $artigo?->ano_publicacao,
            'artigo_link_acesso' => $artigo?->link_acesso,
        ];

        $artigoBemMaterial->delete();

        Auditoria::create([
            'usuario_id' => $request->user()->id,
            'entidade_tipo' => ArtigoBemMaterial::class,
            'entidade_id' => $artigoBemMaterial->id,
            'curadoria_id' => null,
            'operacao' => 'Remoção',
            'meio' => 'Manual',
            'data_hora' => now(),
            'valor_anterior' => $anterior,
            'valor_novo' => null,
        ]);

        return response()->json(null, 204);
    }
}
