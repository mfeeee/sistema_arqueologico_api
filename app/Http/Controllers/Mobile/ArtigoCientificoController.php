<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ArtigoBemMaterial;
use App\Models\ArtigoCientifico;
use App\Models\BemMaterial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArtigoCientificoController extends Controller
{
    /**
     * Busca um artigo pelo DOI para pré-preencher o formulário de submissão.
     * GET /mobile/artigos-cientificos/buscar-doi?doi=10.xxxx/...
     */
    public function buscarPorDoi(Request $request): JsonResponse
    {
        $request->validate(['doi' => ['required', 'string']]);

        $artigo = ArtigoCientifico::where('doi', $request->doi)->first();

        if (! $artigo) {
            return response()->json(['artigo' => null]);
        }

        return response()->json(['artigo' => $artigo]);
    }

    /**
     * Lista os artigos aprovados vinculados a um bem material.
     * GET /mobile/bens-materiais/{bemMaterial}/artigos
     */
    public function porBemMaterial(Request $request, string $bemMaterialId): JsonResponse
    {
        $bemMaterial = BemMaterial::findOrFail($bemMaterialId);

        $this->authorize('view', $bemMaterial);

        $vinculos = ArtigoBemMaterial::with('artigo')
            ->where('bem_material_id', $bemMaterial->id)
            ->orderByDesc('created_at')
            ->get();

        $artigos = $vinculos->map(fn (ArtigoBemMaterial $v) => [
            'id' => $v->artigo->id,
            'vinculo_id' => $v->id,
            'titulo' => $v->artigo->titulo,
            'autores' => $v->artigo->autores,
            'ano_publicacao' => $v->artigo->ano_publicacao,
            'periodico' => $v->artigo->periodico,
            'doi' => $v->artigo->doi,
            'link_acesso' => $v->artigo->link_acesso,
            'idioma' => $v->artigo->idioma,
            'resumo' => $v->artigo->resumo,
            'tipo_mencao' => $v->tipo_mencao,
            'trecho_relevante' => $v->trecho_relevante,
        ]);

        return response()->json([
            'bem_material_id' => $bemMaterial->id,
            'artigos' => $artigos,
        ]);
    }
}
