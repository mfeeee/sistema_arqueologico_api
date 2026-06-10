<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmissaoArtigo\StoreSubmissaoArtigoRequest;
use App\Models\Curadoria;
use App\Models\SubmissaoArtigo;
use App\Models\SubmissaoAutor;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SubmissaoArtigoController extends Controller
{
    /**
     * Cria uma submissão de artigo científico e abre automaticamente
     * uma curadoria polimórfica vinculada (entidade_tipo = submissao_artigo).
     */
    public function store(StoreSubmissaoArtigoRequest $request): JsonResponse
    {
        $submissao = DB::transaction(function () use ($request): SubmissaoArtigo {
            $submissao = SubmissaoArtigo::create([
                'usuario_id' => $request->user()->id,
                'bem_material_id' => $request->bem_material_id,
                'artigo_id' => $request->artigo_id,
                'doi' => $request->doi,
                'titulo' => $request->titulo,
                'ano_publicacao' => $request->ano_publicacao,
                'periodico' => $request->periodico,
                'idioma' => $request->input('idioma', 'pt'),
                'resumo' => $request->resumo,
                'link_acesso' => $request->link_acesso,
                'tipo_mencao' => $request->tipo_mencao,
                'trecho_relevante' => $request->trecho_relevante,
                'status' => 'pendente',
            ]);

            foreach ($request->input('autores', []) as $ordem => $nomeAutor) {
                SubmissaoAutor::create([
                    'submissao_id' => $submissao->id,
                    'nome_autor' => $nomeAutor,
                    'ordem' => $ordem,
                ]);
            }

            Curadoria::create([
                'entidade_tipo' => 'submissao_artigo',
                'entidade_id' => $submissao->id,
                'usuario_id' => $request->user()->id,
                'status' => 'pendente',
                'bem_material_id' => $request->bem_material_id,
            ]);

            return $submissao;
        });

        return response()->json($submissao->load(['bemMaterial', 'artigo.autores', 'autores']), 201);
    }
}
