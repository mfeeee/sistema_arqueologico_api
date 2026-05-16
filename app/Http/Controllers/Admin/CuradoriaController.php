<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AcaoResultanteCuradoria;
use App\Enums\StatusColeta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Curadoria\AvaliarCuradoriaRequest;
use App\Models\Auditoria;
use App\Models\BemMaterial;
use App\Models\Curadoria;
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

        $curadorias = Curadoria::with(['coleta', 'bemMaterial', 'curador'])
            ->where('status', $status)
            ->orderBy('created_at')
            ->paginate(20);

        return response()->json($curadorias);
    }

    public function show(Curadoria $curadoria): JsonResponse
    {
        $this->authorize('view', $curadoria);

        return response()->json($curadoria->load(['coleta', 'bemMaterial', 'curador']));
    }

    public function porBemMaterial(BemMaterial $bemMaterial): JsonResponse
    {
        $this->authorize('view', $bemMaterial);

        $curadorias = Curadoria::with(['coleta', 'curador'])
            ->where('bem_material_id', $bemMaterial->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($curadorias);
    }

    public function avaliar(AvaliarCuradoriaRequest $request, Curadoria $curadoria): JsonResponse
    {
        $this->authorize('avaliar', $curadoria);

        DB::transaction(function () use ($request, $curadoria) {
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

                if (! empty($campos)) {
                    $bem->update($campos);

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
        });

        return response()->json($curadoria->fresh(['coleta', 'bemMaterial']));
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

        $bem = BemMaterial::create([
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
        ]);

        DB::statement(
            'UPDATE bens_materiais SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
            [$bem->longitude, $bem->latitude, $bem->id]
        );

        return $bem;
    }
}
