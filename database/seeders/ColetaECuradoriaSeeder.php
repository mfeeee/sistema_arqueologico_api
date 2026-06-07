<?php

namespace Database\Seeders;

use App\Enums\ArtefatoBem;
use App\Models\ArtefatoTipo;
use App\Models\Auditoria;
use App\Models\BemMaterial;
use App\Models\Coleta;
use App\Models\ColetaArtefatoTipo;
use App\Models\Curadoria;
use App\Models\Localizacao;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Popula os seis cenários de curadoria do sistema.
 *
 * ┌──────────┬────────────────────────────────────────────────────────────────────────┐
 * │ Cenário  │ Descrição                                                              │
 * ├──────────┼────────────────────────────────────────────────────────────────────────┤
 * │ Caso A   │ 3 coletas pendentes → curadorias pendentes (intenção: criarSitio)     │
 * │          │   Sem bem_material, sem data_avaliacao, sem auditoria.                 │
 * ├──────────┼────────────────────────────────────────────────────────────────────────┤
 * │ Caso B   │ 3 coletas aprovadas → criarSitio → novo BemMaterial (PI-NOVO-0101...) │
 * │          │   + auditoria Inserção (valor_anterior=null, valor_novo=snapshot bem)  │
 * ├──────────┼────────────────────────────────────────────────────────────────────────┤
 * │ Caso C   │ 3 coletas aprovadas → atualizarSitio PREENCHENDO campo que era null   │
 * │          │   Alvo: PI-BASE-0004 (nomes_populares), PI-BASE-0005 (meios_acesso),  │
 * │          │          PI-BASE-0006 (municipio + cep)                                │
 * │          │   + auditoria Alteração (valor_anterior={campo:null}, valor_novo=val)  │
 * ├──────────┼────────────────────────────────────────────────────────────────────────┤
 * │ Caso D   │ 3 coletas aprovadas → atualizarSitio MODIFICANDO campo com valor      │
 * │          │   Alvo: PI-BASE-0001 (nome_bem), PI-BASE-0002 (descricao_atualizacao) │
 * │          │          PI-BASE-0003 (meios_acesso)                                   │
 * │          │   + auditoria Alteração (valor_anterior=full snapshot, valor_novo=1 campo) │
 * ├──────────┼────────────────────────────────────────────────────────────────────────┤
 * │ Caso E   │ 3 coletas aprovadas → atualizarSitio MÚLTIPLOS CAMPOS (null + valor)  │
 * │          │   Alvo: PI-NOVO-0101, PI-NOVO-0102, PI-NOVO-0103 (criados no Caso B)  │
 * │          │   Combina campos que eram null → valor E campos existentes → novo val  │
 * │          │   + auditoria Alteração com múltiplos campos em valor_novo             │
 * ├──────────┼────────────────────────────────────────────────────────────────────────┤
 * │ Caso F   │ 3 coletas rejeitadas → bem_material_id=null, sem auditoria de bem     │
 * └──────────┴────────────────────────────────────────────────────────────────────────┘
 *
 * Obs.: AuditoriaManualSeeder (Caso G) cria auditorias manuais independentes.
 */
class ColetaECuradoriaSeeder extends Seeder
{
    /** @var array<string, ArtefatoTipo> Tipos indexados por nome, carregados no início do run(). */
    private array $artefatoTipos = [];

    // ─── helper: cria Localizacao com geom PostGIS ──────────────────────────────

    /** @param array<string, mixed> $dados */
    private function criarLocalizacao(array $dados): Localizacao
    {
        $localizacao = Localizacao::create([
            'uf' => $dados['uf'] ?? null,
            'municipio' => $dados['municipio'] ?? null,
        ]);

        DB::statement(
            'UPDATE localizacoes SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
            [$dados['longitude'], $dados['latitude'], $localizacao->id]
        );

        return $localizacao;
    }

    // ─── helper: cria Coleta com localizacao_id ─────────────────────────────────

    /** @param array<string, mixed> $dados */
    private function criarColeta(array $dados): Coleta
    {
        $localizacao = $this->criarLocalizacao($dados);

        // Remove chaves que pertencem apenas à Localizacao e não existem em coletas
        $coletaDados = array_diff_key($dados, array_flip(['municipio']));

        return Coleta::create([...$coletaDados, 'localizacao_id' => $localizacao->id]);
    }

    // ─── helper: vincula artefatos da coleta à tabela coleta_artefato_tipos ─────

    /** @param string[] $artefatos Valores do enum ArtefatoBem (ex: 'litico', 'ceramica'). */
    private function vincularArtefatoTipos(Coleta $coleta, array $artefatos): void
    {
        foreach ($artefatos as $valor) {
            try {
                $nome = ArtefatoBem::from($valor)->label();
            } catch (\ValueError) {
                continue;
            }

            $tipo = $this->artefatoTipos[$nome] ?? null;

            if ($tipo) {
                ColetaArtefatoTipo::firstOrCreate([
                    'coleta_id' => $coleta->id,
                    'artefato_tipo_id' => $tipo->id,
                ]);
            }
        }
    }

    // ─── helper: snapshot completo de um BemMaterial para auditoria ─────────────

    /** @return array<string, mixed> */
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

    public function run(): void
    {
        $this->artefatoTipos = ArtefatoTipo::all()->keyBy('nome')->all();

        $coletor = User::where('email', 'coletor@arqueologia.test')->firstOrFail();
        $curador = User::where('email', 'curador@arqueologia.test')->firstOrFail();
        $admin = User::where('email', 'admin@arqueologia.test')->firstOrFail();

        $bemBase = BemMaterial::whereIn('codigo_iphan', [
            'PI-BASE-0001', 'PI-BASE-0002', 'PI-BASE-0003',
            'PI-BASE-0004', 'PI-BASE-0005', 'PI-BASE-0006',
        ])->get()->keyBy('codigo_iphan');

        // ════════════════════════════════════════════════════════════════════════
        // CASO A — Coletas Pendentes (intenção: criarSitio quando avaliado)
        // ════════════════════════════════════════════════════════════════════════
        $this->command->info('Caso A: coletas pendentes (intent: criarSitio)...');

        $dadosA = [
            [
                'latitude' => -8.4901, 'longitude' => -42.6102,
                'nome_bem' => 'Abrigo da Anta — Prospecção 2026',
                'natureza_bem' => 'bemArqueologico', 'tipo_bem' => 'sitio',
                'artefatos' => ['litico', 'ceramica'],
                'dados_coletados' => [
                    'midias' => [['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/anta-2026-01.jpg']],
                    'responsavel' => ['nome' => 'Josué Pereira', 'telefone' => '86991230001'],
                ],
                'data_coleta' => Carbon::now()->subDays(3),
            ],
            [
                'latitude' => -4.1023, 'longitude' => -41.7081,
                'nome_bem' => 'Gruta das Serras — Levantamento Inicial',
                'natureza_bem' => 'bemArqueologico', 'tipo_bem' => 'sitio',
                'artefatos' => ['litico', 'carvao'],
                'dados_coletados' => [
                    'midias' => [['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/gruta-serras-01.jpg']],
                    'responsavel' => ['nome' => 'Francisca Lima', 'telefone' => '86991230002'],
                ],
                'data_coleta' => Carbon::now()->subDays(2),
            ],
            [
                'latitude' => -9.2891, 'longitude' => -43.3175,
                'nome_bem' => 'Toca do Caracol Vermelho — Novo Achado',
                'natureza_bem' => 'bemArqueologico', 'tipo_bem' => 'sitio',
                'artefatos' => ['ceramica', 'litico'],
                'dados_coletados' => [
                    'midias' => [['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/caracol-vermelho-01.jpg']],
                    'responsavel' => ['nome' => 'Pedro Santana', 'telefone' => '86991230003'],
                ],
                'data_coleta' => Carbon::now()->subDays(1),
            ],
        ];

        foreach ($dadosA as $d) {
            $coleta = $this->criarColeta([
                'usuario_id' => $coletor->id,
                'data_coleta' => $d['data_coleta'],
                'latitude' => $d['latitude'],
                'longitude' => $d['longitude'],
                'nome_bem' => $d['nome_bem'],
                'natureza_bem' => $d['natureza_bem'],
                'tipo_bem' => $d['tipo_bem'],
                'uf' => 'PI',
                'artefatos' => $d['artefatos'],
                'status_sincronizacao' => 'pendente',
                'versao' => 1,
                'dados_coletados' => $d['dados_coletados'],
            ]);

            $this->vincularArtefatoTipos($coleta, $d['artefatos']);

            Curadoria::create([
                'entidade_tipo' => 'coleta',
                'entidade_id' => $coleta->id,
                'bem_material_id' => null,
                'usuario_id' => $coletor->id,
                'status' => 'pendente',
                'acao_resultante' => null,
                'data_avaliacao' => null,
                'observacao' => null,
            ]);
        }

        // ════════════════════════════════════════════════════════════════════════
        // CASO B — Aprovação: Novo Sítio (criarSitio)
        // ════════════════════════════════════════════════════════════════════════
        $this->command->info('Caso B: aprovado → criarSitio + auditoria Inserção...');

        $dadosB = [
            [
                'latitude' => -8.5231, 'longitude' => -42.5934,
                'nome_bem' => 'Sítio do Caldeirão das Pedras',
                'natureza_bem' => 'bemArqueologico', 'tipo_bem' => 'sitio',
                'artefatos' => ['litico', 'carvao'],
                'dados_coletados' => [
                    'midias' => [['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/caldeirnao-1.jpg']],
                    'responsavel' => ['nome' => 'Mariana Costa', 'telefone' => '86991230011'],
                ],
                'data_coleta' => Carbon::now()->subDays(15),
            ],
            [
                'latitude' => -4.0782, 'longitude' => -41.6743,
                'nome_bem' => 'Abrigo das Galinhas de Pedra',
                'natureza_bem' => 'bemArqueologico', 'tipo_bem' => 'sitio',
                'artefatos' => ['ceramica', 'litico'],
                'dados_coletados' => [
                    'midias' => [['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/galinhas-pedra-1.jpg']],
                    'responsavel' => ['nome' => 'Antônio Barros', 'telefone' => '86991230012'],
                ],
                'data_coleta' => Carbon::now()->subDays(14),
            ],
            [
                'latitude' => -10.1356, 'longitude' => -44.9715,
                'nome_bem' => 'Toca das Raízes Fósseis',
                'natureza_bem' => 'bemPaleontologico', 'tipo_bem' => 'colecao',
                'artefatos' => ['sedimento', 'ossosFaunisticos'],
                'dados_coletados' => [
                    'midias' => [['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/raizes-fosseis-1.jpg']],
                    'responsavel' => ['nome' => 'Luciana Araújo', 'telefone' => '86991230013'],
                ],
                'data_coleta' => Carbon::now()->subDays(13),
            ],
        ];

        $iphanSeqB = 101;
        $bemNovos = []; // referenciados no Caso E

        foreach ($dadosB as $d) {
            $coleta = $this->criarColeta([
                'usuario_id' => $coletor->id,
                'data_coleta' => $d['data_coleta'],
                'latitude' => $d['latitude'],
                'longitude' => $d['longitude'],
                'nome_bem' => $d['nome_bem'],
                'natureza_bem' => $d['natureza_bem'],
                'tipo_bem' => $d['tipo_bem'],
                'uf' => 'PI',
                'artefatos' => $d['artefatos'],
                'status_sincronizacao' => 'sincronizado',
                'versao' => 1,
                'dados_coletados' => $d['dados_coletados'],
            ]);

            $this->vincularArtefatoTipos($coleta, $d['artefatos']);

            $codigoIphan = 'PI-NOVO-'.str_pad($iphanSeqB++, 4, '0', STR_PAD_LEFT);

            $bem = BemMaterial::create([
                'coleta_id' => $coleta->id,
                'codigo_iphan' => $codigoIphan,
                'nome_bem' => $d['nome_bem'],
                'natureza' => $d['natureza_bem'],
                'tipo' => $d['tipo_bem'],
                'uf' => 'PI',
                'latitude' => $d['latitude'],
                'longitude' => $d['longitude'],
                'geojson' => ['type' => 'Point', 'coordinates' => [$d['longitude'], $d['latitude']]],
                'artefatos' => $d['artefatos'],
                'publicado' => false,
                'ano_registro' => Carbon::now()->year,
                'descricao_atualizacao' => 'Sítio criado via curadoria aprovada em '.Carbon::now()->subDays(10)->toDateString().'.',
                // nomes_populares, meios_acesso, municipio, cep, endereco = null (serão preenchidos no Caso E)
            ]);

            DB::statement(
                'UPDATE bens_materiais SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
                [$bem->longitude, $bem->latitude, $bem->id]
            );

            $bemNovos[] = $bem;

            $curadoria = Curadoria::create([
                'entidade_tipo' => 'coleta',
                'entidade_id' => $coleta->id,
                'bem_material_id' => $bem->id,
                'usuario_id' => $curador->id,
                'status' => 'aprovado',
                'acao_resultante' => 'criarSitio',
                'data_avaliacao' => Carbon::now()->subDays(10),
                'observacao' => 'Registro validado. Novo sítio criado com dados da coleta.',
            ]);

            Auditoria::create([
                'usuario_id' => $curador->id,
                'entidade_tipo' => 'App\\Models\\BemMaterial',
                'entidade_id' => $bem->id,
                'curadoria_id' => $curadoria->id,
                'operacao' => 'Inserção',
                'meio' => 'Auditoria',
                'data_hora' => Carbon::now()->subDays(10),
                'valor_anterior' => null,
                'valor_novo' => $this->snapshot($bem),
            ]);
        }

        // ════════════════════════════════════════════════════════════════════════
        // CASO C — atualizarSitio: preenchendo campo que estava NULL → valor
        // ════════════════════════════════════════════════════════════════════════
        $this->command->info('Caso C: aprovado → atualizarSitio (null → valor) + auditoria Alteração...');

        // C1 — PI-BASE-0004: nomes_populares era null → preenche
        $bemC1 = $bemBase['PI-BASE-0004'];
        $snapC1 = $this->snapshot($bemC1);
        $novoNomePopular = 'Cânion do Poti — Gruta Abrigada';

        $coletaC1 = $this->criarColeta([
            'usuario_id' => $coletor->id,
            'data_coleta' => Carbon::now()->subDays(22),
            'latitude' => (float) $bemC1->latitude,
            'longitude' => (float) $bemC1->longitude,
            'nome_bem' => $bemC1->nome_bem,
            'natureza_bem' => $bemC1->natureza?->value ?? $bemC1->natureza,
            'tipo_bem' => $bemC1->tipo?->value ?? $bemC1->tipo,
            'uf' => $bemC1->uf,
            'municipio' => $bemC1->municipio,
            'artefatos' => $bemC1->artefatos ?? [],
            'status_sincronizacao' => 'sincronizado',
            'versao' => 2,
            'dados_coletados' => [
                'midias' => [['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/canion-poti-revisao.jpg']],
                'responsavel' => ['nome' => 'Equipe Levantamento Norte', 'telefone' => '86991230021'],
            ],
        ]);

        $this->vincularArtefatoTipos($coletaC1, $bemC1->artefatos ?? []);

        $curadoriaC1 = Curadoria::create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coletaC1->id,
            'bem_material_id' => $bemC1->id,
            'usuario_id' => $curador->id,
            'status' => 'aprovado',
            'acao_resultante' => 'atualizarSitio',
            'data_avaliacao' => Carbon::now()->subDays(20),
            'observacao' => 'Nome popular identificado em campo e registrado pela primeira vez.',
        ]);

        Auditoria::create([
            'usuario_id' => $curador->id,
            'entidade_tipo' => 'App\\Models\\BemMaterial',
            'entidade_id' => $bemC1->id,
            'curadoria_id' => $curadoriaC1->id,
            'operacao' => 'Alteração',
            'meio' => 'Auditoria',
            'data_hora' => Carbon::now()->subDays(20),
            'valor_anterior' => $snapC1,
            'valor_novo' => ['nomes_populares' => $novoNomePopular],
        ]);

        // C2 — PI-BASE-0005: meios_acesso era null → preenche
        $bemC2 = $bemBase['PI-BASE-0005'];
        $snapC2 = $this->snapshot($bemC2);
        $novoMeiosAcesso = 'Acesso pela PI-247, sentido Buriti dos Montes — São Gonçalo do Gurgueia. '
            .'Veículo 4x4 obrigatório. Na estação chuvosa (jan–mar) a pista fica intransitável; '
            .'consultar condições com a SEMARH-PI antes da expedição.';

        $coletaC2 = $this->criarColeta([
            'usuario_id' => $coletor->id,
            'data_coleta' => Carbon::now()->subDays(21),
            'latitude' => (float) $bemC2->latitude,
            'longitude' => (float) $bemC2->longitude,
            'nome_bem' => $bemC2->nome_bem,
            'natureza_bem' => $bemC2->natureza?->value ?? $bemC2->natureza,
            'tipo_bem' => $bemC2->tipo?->value ?? $bemC2->tipo,
            'uf' => $bemC2->uf,
            'municipio' => $bemC2->municipio,
            'artefatos' => $bemC2->artefatos ?? [],
            'status_sincronizacao' => 'sincronizado',
            'versao' => 2,
            'dados_coletados' => [
                'midias' => [['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/nascentes-acesso-2026.jpg']],
                'responsavel' => ['nome' => 'Dra. Camila Nogueira', 'telefone' => '86998123456'],
            ],
        ]);

        $this->vincularArtefatoTipos($coletaC2, $bemC2->artefatos ?? []);

        $curadoriaC2 = Curadoria::create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coletaC2->id,
            'bem_material_id' => $bemC2->id,
            'usuario_id' => $admin->id,
            'status' => 'aprovado',
            'acao_resultante' => 'atualizarSitio',
            'data_avaliacao' => Carbon::now()->subDays(19),
            'observacao' => 'Descrição de acesso levantada em campo pela responsável e registrada.',
        ]);

        Auditoria::create([
            'usuario_id' => $admin->id,
            'entidade_tipo' => 'App\\Models\\BemMaterial',
            'entidade_id' => $bemC2->id,
            'curadoria_id' => $curadoriaC2->id,
            'operacao' => 'Alteração',
            'meio' => 'Auditoria',
            'data_hora' => Carbon::now()->subDays(19),
            'valor_anterior' => $snapC2,
            'valor_novo' => ['meios_acesso' => $novoMeiosAcesso],
        ]);

        // C3 — PI-BASE-0006: municipio e cep eram null → preenche
        $bemC3 = $bemBase['PI-BASE-0006'];
        $snapC3 = $this->snapshot($bemC3);

        $coletaC3 = $this->criarColeta([
            'usuario_id' => $coletor->id,
            'data_coleta' => Carbon::now()->subDays(20),
            'latitude' => (float) $bemC3->latitude,
            'longitude' => (float) $bemC3->longitude,
            'nome_bem' => $bemC3->nome_bem,
            'natureza_bem' => $bemC3->natureza?->value ?? $bemC3->natureza,
            'tipo_bem' => $bemC3->tipo?->value ?? $bemC3->tipo,
            'uf' => $bemC3->uf,
            'artefatos' => $bemC3->artefatos ?? [],
            'status_sincronizacao' => 'sincronizado',
            'versao' => 2,
            'dados_coletados' => [
                'midias' => [['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/toca-cosmos-campo-2026.jpg']],
                'responsavel' => ['nome' => 'Dr. François Parenti', 'telefone' => '86991230022'],
            ],
        ]);

        $this->vincularArtefatoTipos($coletaC3, $bemC3->artefatos ?? []);

        $curadoriaC3 = Curadoria::create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coletaC3->id,
            'bem_material_id' => $bemC3->id,
            'usuario_id' => $curador->id,
            'status' => 'aprovado',
            'acao_resultante' => 'atualizarSitio',
            'data_avaliacao' => Carbon::now()->subDays(18),
            'observacao' => 'Município e CEP confirmados via Correios e IBGE.',
        ]);

        Auditoria::create([
            'usuario_id' => $curador->id,
            'entidade_tipo' => 'App\\Models\\BemMaterial',
            'entidade_id' => $bemC3->id,
            'curadoria_id' => $curadoriaC3->id,
            'operacao' => 'Alteração',
            'meio' => 'Auditoria',
            'data_hora' => Carbon::now()->subDays(18),
            'valor_anterior' => $snapC3,
            'valor_novo' => ['municipio' => 'São Raimundo Nonato', 'cep' => '64770-000'],
        ]);

        // ════════════════════════════════════════════════════════════════════════
        // CASO D — atualizarSitio: modificando campo que JÁ TINHA valor
        // ════════════════════════════════════════════════════════════════════════
        $this->command->info('Caso D: aprovado → atualizarSitio (valor → novo valor) + auditoria Alteração...');

        // D1 — PI-BASE-0001: nome_bem corrigido
        $bemD1 = $bemBase['PI-BASE-0001'];
        $snapD1 = $this->snapshot($bemD1);
        $nomeD1 = 'Sítio Boqueirão da Pedra Furada — Revisão IPHAN 2026';

        $coletaD1 = $this->criarColeta([
            'usuario_id' => $coletor->id,
            'data_coleta' => Carbon::now()->subDays(25),
            'latitude' => (float) $bemD1->latitude,
            'longitude' => (float) $bemD1->longitude,
            'nome_bem' => $nomeD1,
            'natureza_bem' => $bemD1->natureza?->value ?? $bemD1->natureza,
            'tipo_bem' => $bemD1->tipo?->value ?? $bemD1->tipo,
            'uf' => $bemD1->uf,
            'municipio' => $bemD1->municipio,
            'artefatos' => $bemD1->artefatos ?? [],
            'status_sincronizacao' => 'sincronizado',
            'versao' => 3,
            'dados_coletados' => [
                'midias' => [['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/pedra-furada-revisao-2026.jpg']],
                'responsavel' => ['nome' => 'Equipe de Revisão IPHAN', 'telefone' => '86991230031'],
            ],
        ]);

        $this->vincularArtefatoTipos($coletaD1, $bemD1->artefatos ?? []);

        $curadoriaD1 = Curadoria::create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coletaD1->id,
            'bem_material_id' => $bemD1->id,
            'usuario_id' => $admin->id,
            'status' => 'aprovado',
            'acao_resultante' => 'atualizarSitio',
            'data_avaliacao' => Carbon::now()->subDays(23),
            'observacao' => 'Denominação oficial atualizada conforme publicação IPHAN jan/2026.',
        ]);

        Auditoria::create([
            'usuario_id' => $admin->id,
            'entidade_tipo' => 'App\\Models\\BemMaterial',
            'entidade_id' => $bemD1->id,
            'curadoria_id' => $curadoriaD1->id,
            'operacao' => 'Alteração',
            'meio' => 'Auditoria',
            'data_hora' => Carbon::now()->subDays(23),
            'valor_anterior' => $snapD1,
            'valor_novo' => ['nome_bem' => $nomeD1],
        ]);

        // D2 — PI-BASE-0002: descricao_atualizacao revisada
        $bemD2 = $bemBase['PI-BASE-0002'];
        $snapD2 = $this->snapshot($bemD2);
        $descD2 = 'Abrigo com fogueiras pré-históricas datadas de 17.000 AP. '
            .'Nova datação por AMS (2026) confirmou 18.500 ± 300 AP para o nível 6. '
            .'Sedimento estratigráfico preservado em excelente estado de conservação.';

        $coletaD2 = $this->criarColeta([
            'usuario_id' => $coletor->id,
            'data_coleta' => Carbon::now()->subDays(26),
            'latitude' => (float) $bemD2->latitude,
            'longitude' => (float) $bemD2->longitude,
            'nome_bem' => $bemD2->nome_bem,
            'natureza_bem' => $bemD2->natureza?->value ?? $bemD2->natureza,
            'tipo_bem' => $bemD2->tipo?->value ?? $bemD2->tipo,
            'uf' => $bemD2->uf,
            'municipio' => $bemD2->municipio,
            'artefatos' => $bemD2->artefatos ?? [],
            'status_sincronizacao' => 'sincronizado',
            'versao' => 2,
            'dados_coletados' => [
                'midias' => [['tipo' => 'artigo', 'url' => 'https://doi.org/10.1590/ams-toca-boqueirnao-2026']],
                'responsavel' => ['nome' => 'Prof. Eric Boëda', 'telefone' => '86358213890'],
            ],
        ]);

        $this->vincularArtefatoTipos($coletaD2, $bemD2->artefatos ?? []);

        $curadoriaD2 = Curadoria::create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coletaD2->id,
            'bem_material_id' => $bemD2->id,
            'usuario_id' => $curador->id,
            'status' => 'aprovado',
            'acao_resultante' => 'atualizarSitio',
            'data_avaliacao' => Carbon::now()->subDays(24),
            'observacao' => 'Descrição atualizada com resultado da nova datação por AMS publicada em jan/2026.',
        ]);

        Auditoria::create([
            'usuario_id' => $curador->id,
            'entidade_tipo' => 'App\\Models\\BemMaterial',
            'entidade_id' => $bemD2->id,
            'curadoria_id' => $curadoriaD2->id,
            'operacao' => 'Alteração',
            'meio' => 'Auditoria',
            'data_hora' => Carbon::now()->subDays(24),
            'valor_anterior' => $snapD2,
            'valor_novo' => ['descricao_atualizacao' => $descD2],
        ]);

        // D3 — PI-BASE-0003: meios_acesso atualizado
        $bemD3 = $bemBase['PI-BASE-0003'];
        $snapD3 = $this->snapshot($bemD3);
        $acessoD3 = 'Acesso pela PI-111 até Piracuruca; entrada pelo PARNA Sete Cidades — '
            .'nova portaria inaugurada em mar/2026 na km 312. Guia credenciado obrigatório. '
            .'Agendamento prévio pelo app ICMBIO.';

        $coletaD3 = $this->criarColeta([
            'usuario_id' => $coletor->id,
            'data_coleta' => Carbon::now()->subDays(27),
            'latitude' => (float) $bemD3->latitude,
            'longitude' => (float) $bemD3->longitude,
            'nome_bem' => $bemD3->nome_bem,
            'natureza_bem' => $bemD3->natureza?->value ?? $bemD3->natureza,
            'tipo_bem' => $bemD3->tipo?->value ?? $bemD3->tipo,
            'uf' => $bemD3->uf,
            'municipio' => $bemD3->municipio,
            'artefatos' => $bemD3->artefatos ?? [],
            'status_sincronizacao' => 'sincronizado',
            'versao' => 2,
            'dados_coletados' => [
                'midias' => [['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/sete-cidades-portaria-2026.jpg']],
                'responsavel' => ['nome' => 'Msc. Ana Paula Sousa', 'telefone' => '86327644550'],
            ],
        ]);

        $this->vincularArtefatoTipos($coletaD3, $bemD3->artefatos ?? []);

        $curadoriaD3 = Curadoria::create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coletaD3->id,
            'bem_material_id' => $bemD3->id,
            'usuario_id' => $admin->id,
            'status' => 'aprovado',
            'acao_resultante' => 'atualizarSitio',
            'data_avaliacao' => Carbon::now()->subDays(25),
            'observacao' => 'Novo ponto de acesso confirmado pelo ICMBIO. Meios de acesso corrigidos.',
        ]);

        Auditoria::create([
            'usuario_id' => $admin->id,
            'entidade_tipo' => 'App\\Models\\BemMaterial',
            'entidade_id' => $bemD3->id,
            'curadoria_id' => $curadoriaD3->id,
            'operacao' => 'Alteração',
            'meio' => 'Auditoria',
            'data_hora' => Carbon::now()->subDays(25),
            'valor_anterior' => $snapD3,
            'valor_novo' => ['meios_acesso' => $acessoD3],
        ]);

        // ════════════════════════════════════════════════════════════════════════
        // CASO E — atualizarSitio: MÚLTIPLOS CAMPOS (null→valor + valor→valor)
        // Alvos: PI-NOVO-0101, 0102, 0103 criados no Caso B
        // ════════════════════════════════════════════════════════════════════════
        $this->command->info('Caso E: aprovado → atualizarSitio (múltiplos campos) + auditoria Alteração...');

        $alteracoesE = [
            // PI-NOVO-0101: adiciona municipio (null→valor) + corrige lat/lng (valor→valor)
            [
                'municipio_novo' => 'São Raimundo Nonato',
                'lat_novo' => -8.5255,
                'lng_novo' => -42.5918,
                'observacao' => 'Coordenadas corrigidas em campo; município confirmado pelo IBGE.',
                'valor_novo_fn' => fn (BemMaterial $b, array $alt) => [
                    'municipio' => $alt['municipio_novo'],
                    'latitude' => $alt['lat_novo'],
                    'longitude' => $alt['lng_novo'],
                ],
                'dados_coletados' => [
                    'midias' => [['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/caldeirnao-correcao.jpg']],
                    'responsavel' => ['nome' => 'Equipe GPS UFPI', 'telefone' => '86991230041'],
                ],
                'avaliado_em' => Carbon::now()->subDays(7),
            ],
            // PI-NOVO-0102: adiciona nomes_populares (null→valor) + adiciona meios_acesso (null→valor)
            [
                'nomes_populares_novo' => 'Abrigo das Galinhas',
                'meios_acesso_novo' => 'Acesso pela PI-113, saída Piracuruca sentido Batalha. '
                    .'Trilha de 3 km a partir do km 28. Horário de visita: 8h–16h.',
                'observacao' => 'Nome popular e rota de acesso coletados junto à comunidade local.',
                'valor_novo_fn' => fn (BemMaterial $b, array $alt) => [
                    'nomes_populares' => $alt['nomes_populares_novo'],
                    'meios_acesso' => $alt['meios_acesso_novo'],
                ],
                'dados_coletados' => [
                    'midias' => [['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/galinhas-acesso.jpg']],
                    'responsavel' => ['nome' => 'Antônio Barros', 'telefone' => '86991230012'],
                ],
                'avaliado_em' => Carbon::now()->subDays(6),
            ],
            // PI-NOVO-0103: adiciona municipio + cep (null→valor) + atualiza artefatos (valor→valor)
            [
                'municipio_novo' => 'Buriti dos Montes',
                'cep_novo' => '64660-000',
                'artefatos_novos' => ['sedimento', 'ossosFaunisticos', 'litico', 'carvao'],
                'observacao' => 'Município e CEP confirmados. Novos artefatos encontrados em prospecção complementar.',
                'valor_novo_fn' => fn (BemMaterial $b, array $alt) => [
                    'municipio' => $alt['municipio_novo'],
                    'cep' => $alt['cep_novo'],
                    'artefatos' => $alt['artefatos_novos'],
                ],
                'dados_coletados' => [
                    'midias' => [
                        ['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/raizes-fosseis-2026.jpg'],
                        ['tipo' => 'video',  'url' => 'https://arqueologia.example.com/videos/raizes-fosseis-prospeccao.mp4'],
                    ],
                    'responsavel' => ['nome' => 'Luciana Araújo', 'telefone' => '86991230013'],
                ],
                'avaliado_em' => Carbon::now()->subDays(5),
            ],
        ];

        foreach ($bemNovos as $idx => $bemAlvo) {
            $alt = $alteracoesE[$idx];
            $snapE = $this->snapshot($bemAlvo->fresh());
            $avaliadoEm = $alt['avaliado_em'];
            $artefatosE = $alt['artefatos_novos'] ?? $bemAlvo->artefatos ?? [];

            $coletaE = $this->criarColeta([
                'usuario_id' => $coletor->id,
                'data_coleta' => $avaliadoEm->copy()->subDays(3),
                'latitude' => (float) $bemAlvo->latitude,
                'longitude' => (float) $bemAlvo->longitude,
                'nome_bem' => $bemAlvo->nome_bem.' — Complementação',
                'natureza_bem' => $bemAlvo->natureza?->value ?? $bemAlvo->natureza,
                'tipo_bem' => $bemAlvo->tipo?->value ?? $bemAlvo->tipo,
                'uf' => $bemAlvo->uf ?? 'PI',
                'artefatos' => $artefatosE,
                'status_sincronizacao' => 'sincronizado',
                'versao' => 2,
                'dados_coletados' => $alt['dados_coletados'],
            ]);

            $this->vincularArtefatoTipos($coletaE, $artefatosE);

            $curadoriaE = Curadoria::create([
                'entidade_tipo' => 'coleta',
                'entidade_id' => $coletaE->id,
                'bem_material_id' => $bemAlvo->id,
                'usuario_id' => $curador->id,
                'status' => 'aprovado',
                'acao_resultante' => 'atualizarSitio',
                'data_avaliacao' => $avaliadoEm,
                'observacao' => $alt['observacao'],
            ]);

            Auditoria::create([
                'usuario_id' => $curador->id,
                'entidade_tipo' => 'App\\Models\\BemMaterial',
                'entidade_id' => $bemAlvo->id,
                'curadoria_id' => $curadoriaE->id,
                'operacao' => 'Alteração',
                'meio' => 'Auditoria',
                'data_hora' => $avaliadoEm,
                'valor_anterior' => $snapE,
                'valor_novo' => ($alt['valor_novo_fn'])($bemAlvo, $alt),
            ]);
        }

        // ════════════════════════════════════════════════════════════════════════
        // CASO F — Rejeição (sem bem_material, sem auditoria de bem)
        // ════════════════════════════════════════════════════════════════════════
        $this->command->info('Caso F: rejeitado → rejeitar (sem bem_material, sem auditoria)...');

        $dadosF = [
            [
                'latitude' => -8.5300, 'longitude' => -42.6200,
                'nome_bem' => 'Fragmentos Isolados — Zona A',
                'natureza_bem' => 'bemArqueologico', 'tipo_bem' => 'acervoOuColecao',
                'artefatos' => ['ceramica'],
                'observacao' => 'Material muito fragmentado; contexto arqueológico incerto. Necessita re-prospecção.',
                'data_coleta' => Carbon::now()->subDays(45),
            ],
            [
                'latitude' => -4.1100, 'longitude' => -41.7100,
                'nome_bem' => 'Possível Sítio das Pedras Grandes',
                'natureza_bem' => 'bemArqueologico', 'tipo_bem' => 'sitio',
                'artefatos' => ['litico'],
                'observacao' => 'Descrição incompleta. Coordenadas inconsistentes com levantamento IBGE.',
                'data_coleta' => Carbon::now()->subDays(44),
            ],
            [
                'latitude' => -5.1200, 'longitude' => -42.8300,
                'nome_bem' => 'Ocorrência de Fragmentos Históricos',
                'natureza_bem' => 'bemArqueologico', 'tipo_bem' => 'bemOuConjunto',
                'artefatos' => ['faianca', 'metalico'],
                'observacao' => 'Material de período histórico tardio; não atende critérios de tombamento arqueológico.',
                'data_coleta' => Carbon::now()->subDays(43),
            ],
        ];

        foreach ($dadosF as $d) {
            $coleta = $this->criarColeta([
                'usuario_id' => $coletor->id,
                'data_coleta' => $d['data_coleta'],
                'latitude' => $d['latitude'],
                'longitude' => $d['longitude'],
                'nome_bem' => $d['nome_bem'],
                'natureza_bem' => $d['natureza_bem'],
                'tipo_bem' => $d['tipo_bem'],
                'uf' => 'PI',
                'artefatos' => $d['artefatos'],
                'status_sincronizacao' => 'sincronizado',
                'versao' => 1,
                'dados_coletados' => [
                    'midias' => [],
                    'responsavel' => ['nome' => 'Equipe de Campo', 'telefone' => '86000000000'],
                ],
            ]);

            $this->vincularArtefatoTipos($coleta, $d['artefatos']);

            Curadoria::create([
                'entidade_tipo' => 'coleta',
                'entidade_id' => $coleta->id,
                'bem_material_id' => null,
                'usuario_id' => $admin->id,
                'status' => 'rejeitado',
                'acao_resultante' => 'rejeitar',
                'data_avaliacao' => Carbon::now()->subDays(40),
                'observacao' => $d['observacao'],
            ]);
        }

        $this->command->info('ColetaECuradoriaSeeder: cenários A–F concluídos.');
    }
}
