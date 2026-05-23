<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AcaoResultanteCuradoria;
use App\Enums\StatusColeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Curadoria\AvaliarCuradoriaRequest;
use App\Models\ArtigoBemMaterial;
use App\Models\ArtigoCientifico;
use App\Models\Auditoria;
use App\Models\BemMaterial;
use App\Models\Curadoria;
use App\Models\SubmissaoArtigo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CuradoriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Curadoria::class);

        $status = $request->filled('status') ? $request->status : 'pendente';

        $curadorias = Curadoria::with(['bemMaterial', 'curador'])
            ->where('status', $status)
            ->orderBy('created_at')
            ->paginate(20);

        // Carrega a entidade relacionada de acordo com o tipo de cada curadoria
        $curadorias->getCollection()->transform(
            fn (Curadoria $c) => $this->carregarEntidade($c)
        );

        return response()->json($curadorias);
    }

    public function show(Curadoria $curadoria): JsonResponse
    {
        $this->authorize('view', $curadoria);

        $curadoria->load(['bemMaterial', 'curador']);

        return response()->json($this->carregarEntidade($curadoria));
    }

    public function porBemMaterial(BemMaterial $bemMaterial): JsonResponse
    {
        $this->authorize('view', $bemMaterial);

        $curadorias = Curadoria::with(['curador'])
            ->where('bem_material_id', $bemMaterial->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        $curadorias->getCollection()->transform(
            fn (Curadoria $c) => $this->carregarEntidade($c)
        );

        return response()->json($curadorias);
    }

    /**
     * Lista todos os usuários que contribuíram com dados aprovados para um bem material.
     * Agrega coletas aprovadas e submissões de artigos aprovadas.
     * GET /admin/bens-materiais/{id}/colaboradores
     */
    public function colaboradores(BemMaterial $bemMaterial): JsonResponse
    {
        $this->authorize('view', $bemMaterial);

        $colaboradores = DB::select("
            SELECT
                u.id,
                u.name AS nome,
                u.email,
                u.classificacao,
                contrib.origem,
                contrib.total AS total_contribuicoes
            FROM (
                SELECT c.usuario_id, 'coleta' AS origem, COUNT(*) AS total
                FROM curadorias cur
                JOIN coletas c ON c.id = cur.entidade_id AND cur.entidade_tipo = 'coleta'
                WHERE cur.bem_material_id = ?
                  AND cur.status = 'aprovado'
                  AND cur.acao_resultante IN ('criarSitio', 'atualizarSitio')
                GROUP BY c.usuario_id

                UNION ALL

                SELECT sa.usuario_id, 'artigo' AS origem, COUNT(DISTINCT abm.id) AS total
                FROM artigo_bem_material abm
                JOIN submissoes_artigos sa
                    ON sa.artigo_id = abm.artigo_id
                    AND sa.bem_material_id = abm.bem_material_id
                    AND sa.status = 'aprovado'
                WHERE abm.bem_material_id = ?
                GROUP BY sa.usuario_id
            ) contrib
            JOIN users u ON u.id = contrib.usuario_id
            ORDER BY u.name, contrib.origem
        ", [$bemMaterial->id, $bemMaterial->id]);

        return response()->json([
            'bem_material_id' => $bemMaterial->id,
            'colaboradores' => $colaboradores,
        ]);
    }


    public function avaliar(AvaliarCuradoriaRequest $request, Curadoria $curadoria): JsonResponse
    {
        $this->authorize('avaliar', $curadoria);

        DB::transaction(function () use ($request, $curadoria) {
            match ($curadoria->entidade_tipo) {
                'coleta' => $this->avaliarColeta($request, $curadoria),
                'submissao_artigo' => $this->avaliarSubmissaoArtigo($request, $curadoria),
                default => null,
            };
        });

        return response()->json($this->carregarEntidade($curadoria->fresh(['bemMaterial'])));
    }

    /**
     * Carrega a entidade relacionada na curadoria com base no entidade_tipo
     * e a embute no modelo como atributo nomeado (ex.: 'coleta').
     * Mantém compatibilidade com o web que lê raw.get("coleta").
     */
    private function carregarEntidade(Curadoria $curadoria): Curadoria
    {
        match ($curadoria->entidade_tipo) {
            'coleta' => $curadoria->setRelation('coleta', $curadoria->coleta),
            'submissao_artigo' => $curadoria->setRelation(
                'submissao_artigo',
                SubmissaoArtigo::with(['bemMaterial', 'artigo'])->find($curadoria->entidade_id)
            ),
            default => null,
        };

        return $curadoria;
    }

    /**
     * Processa a avaliação de curadorias do tipo 'coleta'.
     * Lógica extraída de avaliar() para permitir o dispatcher polimórfico.
     */
    private function avaliarColeta(AvaliarCuradoriaRequest $request, Curadoria $curadoria): void
    {
        $acao = AcaoResultanteCuradoria::from($request->acao_resultante);
        $bemMaterialId = null;

        if ($acao === AcaoResultanteCuradoria::CRIAR_SITIO) {
            $bem = $this->criarBemMaterial($curadoria, (bool) $request->input('publicado', false));
            $bemMaterialId = $bem->id;

            Auditoria::create([
                'usuario_id' => $request->user()->id,
                'entidade_tipo' => BemMaterial::class,
                'entidade_id' => $bem->id,
                'curadoria_id' => $curadoria->id,
                'operacao' => 'Inserção',
                'meio' => 'Curadoria',
                'data_hora' => now(),
                'valor_anterior' => null,
                'valor_novo' => $this->snapshot($bem),
            ]);

        } elseif ($acao === AcaoResultanteCuradoria::ATUALIZAR_SITIO) {
            $bem = BemMaterial::findOrFail($request->bem_material_id);
            $bemMaterialId = $bem->id;
            $anterior = $this->snapshot($bem);

            $campos = $this->resolverCampos($request, $curadoria);
            $campos['publicado'] = (bool) $request->input('publicado', false);

            if (! empty($campos)) {
                BemMaterial::withoutEvents(fn () => $bem->update($campos));

                if (array_key_exists('latitude', $campos) || array_key_exists('longitude', $campos)) {
                    $bem->refresh();
                    DB::statement(
                        'UPDATE bens_materiais SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
                        [$bem->longitude, $bem->latitude, $bem->id]
                    );
                }

                Auditoria::create([
                    'usuario_id' => $request->user()->id,
                    'entidade_tipo' => BemMaterial::class,
                    'entidade_id' => $bem->id,
                    'curadoria_id' => $curadoria->id,
                    'operacao' => 'Alteração',
                    'meio' => 'Curadoria',
                    'data_hora' => now(),
                    'valor_anterior' => $anterior,
                    'valor_novo' => array_intersect_key($this->snapshot($bem->fresh()), $campos),
                ]);
            }
        }
        // REJEITAR: $bemMaterialId permanece null

        $curadoria->update([
            'status' => $request->status,
            'acao_resultante' => $request->acao_resultante,
            'bem_material_id' => $bemMaterialId,
            'data_avaliacao' => now(),
            'observacao' => $request->observacao,
            'usuario_id' => $request->user()->id,
        ]);

        $curadoria->coleta->update([
            'status_sincronizacao' => StatusColeta::SINCRONIZADO->value,
        ]);
    }

    /**
     * Processa a avaliação de curadorias do tipo 'submissao_artigo'.
     *
     * Cenário A — artigo_id preenchido (DOI já existia):
     *   Cria apenas o vínculo ArtigoBemMaterial. Auditoria aponta para o vínculo.
     *
     * Cenário B — artigo_id nulo (DOI novo):
     *   Cria ArtigoCientifico e depois o vínculo. Auditoria aponta para o artigo.
     */
    private function avaliarSubmissaoArtigo(AvaliarCuradoriaRequest $request, Curadoria $curadoria): void
    {
        /** @var SubmissaoArtigo $submissao */
        $submissao = SubmissaoArtigo::findOrFail($curadoria->entidade_id);

        if ($request->acao_resultante === AcaoResultanteCuradoria::APROVAR->value) {
            if ($submissao->artigo_id) {
                // Cenário A: artigo já existe, cria só o vínculo
                $vinculo = ArtigoBemMaterial::create([
                    'artigo_id' => $submissao->artigo_id,
                    'bem_material_id' => $submissao->bem_material_id,
                    'tipo_mencao' => $submissao->tipo_mencao,
                    'trecho_relevante' => $submissao->trecho_relevante,
                ]);

                Auditoria::create([
                    'usuario_id' => $request->user()->id,
                    'entidade_tipo' => ArtigoBemMaterial::class,
                    'entidade_id' => $vinculo->id,
                    'curadoria_id' => $curadoria->id,
                    'operacao' => 'Inserção',
                    'meio' => 'Curadoria',
                    'data_hora' => now(),
                    'valor_anterior' => null,
                    'valor_novo' => [
                        'artigo_id' => $vinculo->artigo_id,
                        'bem_material_id' => $vinculo->bem_material_id,
                        'tipo_mencao' => $submissao->tipo_mencao,
                        'trecho_relevante' => $submissao->trecho_relevante,
                    ],
                ]);
            } else {
                // Cenário B: DOI novo, cria o artigo e depois o vínculo
                $artigo = ArtigoCientifico::create([
                    'adicionado_por' => $request->user()->id,
                    'titulo' => $submissao->titulo,
                    'doi' => $submissao->doi,
                    'link_acesso' => $submissao->link_acesso,
                    'autores' => $submissao->autores,
                    'ano_publicacao' => $submissao->ano_publicacao,
                    'periodico' => $submissao->periodico,
                    'idioma' => $submissao->idioma ?? 'pt',
                    'resumo' => $submissao->resumo,
                    'verificado' => true,
                ]);

                ArtigoBemMaterial::create([
                    'artigo_id' => $artigo->id,
                    'bem_material_id' => $submissao->bem_material_id,
                    'tipo_mencao' => $submissao->tipo_mencao,
                    'trecho_relevante' => $submissao->trecho_relevante,
                ]);

                Auditoria::create([
                    'usuario_id' => $request->user()->id,
                    'entidade_tipo' => ArtigoCientifico::class,
                    'entidade_id' => $artigo->id,
                    'curadoria_id' => $curadoria->id,
                    'operacao' => 'Inserção',
                    'meio' => 'Curadoria',
                    'data_hora' => now(),
                    'valor_anterior' => null,
                    'valor_novo' => [
                        'id' => $artigo->id,
                        'titulo' => $artigo->titulo,
                        'doi' => $artigo->doi,
                        'autores' => $artigo->autores,
                        'ano_publicacao' => $artigo->ano_publicacao,
                        'bem_material_id' => $submissao->bem_material_id,
                        'tipo_mencao' => $submissao->tipo_mencao,
                    ],
                ]);

                // Atualiza a submissão com o artigo criado para manter rastreabilidade
                $submissao->update(['artigo_id' => $artigo->id]);
            }

            $submissao->update(['status' => 'aprovado']);
        } else {
            // REJEITAR
            $submissao->update(['status' => 'rejeitado']);
        }

        $curadoria->update([
            'status' => $request->status,
            'acao_resultante' => $request->acao_resultante,
            'data_avaliacao' => now(),
            'observacao' => $request->observacao,
            'usuario_id' => $request->user()->id,
        ]);
    }

    /**
     * Resolve quais campos e valores serão aplicados ao BemMaterial.
     *
     * Se o cliente enviou `campos` (dict field → value) usa esses valores,
     * filtrando apenas as chaves permitidas.
     * Caso contrário, deriva todos os campos não-nulos da coleta.
     *
     * @return array<string, mixed>
     */
    private function resolverCampos(AvaliarCuradoriaRequest $request, Curadoria $curadoria): array
    {
        $allowed = [
            'nome_bem', 'nomes_populares', 'natureza', 'tipo', 'artefatos',
            'meios_acesso', 'uf', 'municipio', 'cep', 'endereco',
            'latitude', 'longitude', 'ano_registro', 'descricao_atualizacao',
            'publicado',
        ];

        // Campos explicitamente selecionados pelo curador no frontend
        if ($request->filled('campos') && is_array($request->campos)) {
            return array_filter(
                $request->campos,
                fn ($k) => in_array($k, $allowed, true),
                ARRAY_FILTER_USE_KEY
            );
        }

        // Fallback: deriva campos da coleta + dados_coletados
        $coleta = $curadoria->coleta;
        $dados = is_array($coleta->dados_coletados) ? $coleta->dados_coletados : [];

        $candidates = [
            'nome_bem' => $coleta->nome_bem,
            'natureza' => $coleta->natureza_bem?->value ?? $coleta->natureza_bem,
            'tipo' => $coleta->tipo_bem?->value ?? $coleta->tipo_bem,
            'uf' => $coleta->uf,
            'artefatos' => $coleta->artefatos,
            'latitude' => $coleta->latitude,
            'longitude' => $coleta->longitude,
            'nomes_populares' => $dados['nomes_populares'] ?? null,
            'meios_acesso' => $dados['meios_acesso'] ?? null,
            'municipio' => $dados['municipio'] ?? null,
            'cep' => $dados['cep'] ?? null,
            'endereco' => $dados['endereco'] ?? null,
            'descricao_atualizacao' => $dados['descricao_atualizacao'] ?? $dados['descricao'] ?? null,
            'publicado' => $request->boolean('publicado'),
        ];

        return array_filter($candidates, fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Snapshot completo de um BemMaterial para registro de auditoria.
     *
     * @return array<string, mixed>
     */
    private function snapshot(BemMaterial $bem): array
    {
        return [
            'id' => $bem->id,
            'codigo_iphan' => $bem->codigo_iphan,
            'nome_bem' => $bem->nome_bem,
            'nomes_populares' => $bem->nomes_populares,
            'natureza' => $bem->natureza?->value ?? $bem->natureza,
            'tipo' => $bem->tipo?->value ?? $bem->tipo,
            'uf' => $bem->uf,
            'municipio' => $bem->municipio,
            'cep' => $bem->cep,
            'endereco' => $bem->endereco,
            'meios_acesso' => $bem->meios_acesso,
            'latitude' => (float) $bem->latitude,
            'longitude' => (float) $bem->longitude,
            'artefatos' => $bem->artefatos,
            'publicado' => $bem->publicado,
            'ano_registro' => $bem->ano_registro,
            'descricao_atualizacao' => $bem->descricao_atualizacao,
            'updated_at' => $bem->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Cria um novo BemMaterial a partir dos dados da coleta,
     * incluindo campos extras presentes em dados_coletados.
     */
    private function criarBemMaterial(Curadoria $curadoria, bool $publicado = false): BemMaterial
    {
        $coleta = $curadoria->coleta;
        $dados = is_array($coleta->dados_coletados) ? $coleta->dados_coletados : [];

        $bem = BemMaterial::withoutEvents(fn () => BemMaterial::create([
            'coleta_id' => $coleta->id,
            'nome_bem' => $coleta->nome_bem,
            'natureza' => $coleta->natureza_bem?->value,
            'tipo' => $coleta->tipo_bem?->value,
            'uf' => $coleta->uf,
            'latitude' => $coleta->latitude,
            'longitude' => $coleta->longitude,
            'artefatos' => $coleta->artefatos,
            'nomes_populares' => $dados['nomes_populares'] ?? null,
            'meios_acesso' => $dados['meios_acesso'] ?? null,
            'municipio' => $dados['municipio'] ?? null,
            'cep' => $dados['cep'] ?? null,
            'endereco' => $dados['endereco'] ?? null,
            'descricao_atualizacao' => $dados['descricao_atualizacao'] ?? $dados['descricao'] ?? null,
            'publicado' => $publicado,
            'ano_registro' => Carbon::now()->year,
        ]));

        DB::statement(
            'UPDATE bens_materiais SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
            [$bem->longitude, $bem->latitude, $bem->id]
        );

        return $bem;
    }
}
