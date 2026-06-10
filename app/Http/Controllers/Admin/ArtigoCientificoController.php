<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtigoBemMaterial;
use App\Models\ArtigoCientifico;
use App\Models\Auditoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArtigoCientificoController extends Controller
{
    /**
     * Lista todos os artigos científicos cadastrados com contagem de vínculos.
     * GET /admin/artigos-cientificos
     */
    public function index(): JsonResponse
    {
        $artigos = ArtigoCientifico::withCount('vinculos')
            ->with(['adicionadoPor:id,name', 'autores'])
            ->orderBy('titulo')
            ->get();

        return response()->json(['data' => $artigos]);
    }

    /**
     * Retorna os detalhes de um artigo com todos os seus vínculos e bens materiais.
     * GET /admin/artigos-cientificos/{artigo}
     */
    public function show(ArtigoCientifico $artigo): JsonResponse
    {
        $artigo->loadCount('vinculos')
            ->load([
                'adicionadoPor:id,name',
                'autores',
                'vinculos.bemMaterial:id,nome_bem,codigo_iphan,municipio,uf',
            ]);

        return response()->json(['data' => $artigo]);
    }

    /**
     * Exclui um artigo e todos os seus vínculos com bens materiais.
     * Registra uma auditoria de exclusão.
     * DELETE /admin/artigos-cientificos/{artigo}
     */
    public function destroy(Request $request, ArtigoCientifico $artigo): JsonResponse
    {
        $artigo->load('autores');
        $totalVinculos = $artigo->vinculos()->count();

        $anterior = [
            'id' => $artigo->id,
            'titulo' => $artigo->titulo,
            'doi' => $artigo->doi,
            'autores' => $artigo->autores->pluck('nome_autor')->all(),
            'ano_publicacao' => $artigo->ano_publicacao,
            'periodico' => $artigo->periodico,
            'total_vinculos_removidos' => $totalVinculos,
        ];

        // Remove explicitamente os vínculos antes de excluir o artigo.
        $artigo->vinculos()->delete();
        $artigo->delete();

        Auditoria::create([
            'usuario_id' => $request->user()->id,
            'entidade_tipo' => ArtigoCientifico::class,
            'entidade_id' => $artigo->id,
            'curadoria_id' => null,
            'operacao' => 'Exclusão',
            'meio' => 'Manual',
            'data_hora' => now(),
            'valor_anterior' => $anterior,
            'valor_novo' => null,
        ]);

        return response()->json(null, 204);
    }
}
