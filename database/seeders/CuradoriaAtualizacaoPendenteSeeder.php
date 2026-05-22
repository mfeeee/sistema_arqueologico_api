<?php

namespace Database\Seeders;

use App\Models\BemMaterial;
use App\Models\Coleta;
use App\Models\Curadoria;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Insere coletas + curadorias PENDENTES para testar o fluxo de atualizarSitio.
 *
 * Pode ser rodado a qualquer momento sem apagar dados existentes:
 *   php artisan db:seed --class=CuradoriaAtualizacaoPendenteSeeder
 *
 * Pré-requisitos: bens PI-BASE-0001, PI-BASE-0003, PI-BASE-0006 já existem
 * (criados pelo BemMaterialSeeder).
 *
 * ┌──────┬──────────────┬───────────────────────────────────────────────────────┐
 * │  #   │ Alvo         │ Campos propostos pela coleta                          │
 * ├──────┼──────────────┼───────────────────────────────────────────────────────┤
 * │  1   │ PI-BASE-0006 │ municipio + cep + endereco (eram null)                │
 * │  2   │ PI-BASE-0001 │ nomes_populares (null→valor) + descricao_atualizacao  │
 * │  3   │ PI-BASE-0003 │ meios_acesso (valor→novo valor) + artefato adicional  │
 * └──────┴──────────────┴───────────────────────────────────────────────────────┘
 */
class CuradoriaAtualizacaoPendenteSeeder extends Seeder
{
    private function criarColeta(array $dados): Coleta
    {
        $coleta = Coleta::create($dados);

        DB::statement(
            'UPDATE coletas SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
            [$coleta->longitude, $coleta->latitude, $coleta->id]
        );

        return $coleta;
    }

    public function run(): void
    {
        $coletor = User::where('email', 'coletor@arqueologia.test')->firstOrFail();

        $bens = BemMaterial::whereIn('codigo_iphan', [
            'PI-BASE-0001',
            'PI-BASE-0003',
            'PI-BASE-0006',
        ])->get()->keyBy('codigo_iphan');

        // ── 1. PI-BASE-0006: preencher municipio, cep e endereco (eram null) ────
        $bem1 = $bens['PI-BASE-0006'];

        $coleta1 = $this->criarColeta([
            'usuario_id' => $coletor->id,
            'data_coleta' => Carbon::now(),
            'latitude' => (float) $bem1->latitude,
            'longitude' => (float) $bem1->longitude,
            'nome_bem' => $bem1->nome_bem,
            'natureza_bem' => $bem1->natureza?->value ?? $bem1->natureza,
            'tipo_bem' => $bem1->tipo?->value ?? $bem1->tipo,
            'uf' => $bem1->uf,
            'artefatos' => $bem1->artefatos ?? [],
            'status_sincronizacao' => 'pendente',
            'versao' => 2,
            'dados_coletados' => [
                'municipio' => 'São Raimundo Nonato',
                'cep' => '64770-000',
                'endereco' => 'Zona Rural — acesso pela PI-140, km 38',
                'midias' => [['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/cosmos-campo-municipio.jpg']],
                'responsavel' => ['nome' => 'Dr. François Parenti', 'telefone' => '86991230022'],
            ],
        ]);

        Curadoria::create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta1->id,
            'bem_material_id' => $bem1->id,
            'usuario_id' => $coletor->id,
            'status' => 'pendente',
            'acao_resultante' => null,
            'data_avaliacao' => null,
            'observacao' => null,
        ]);

        $this->command->info("Criada curadoria pendente (atualizarSitio) → {$bem1->codigo_iphan}: municipio + cep + endereco");

        // ── 2. PI-BASE-0001: nomes_populares (null→valor) + descricao revisada ──
        $bem2 = $bens['PI-BASE-0001'];

        $coleta2 = $this->criarColeta([
            'usuario_id' => $coletor->id,
            'data_coleta' => Carbon::now(),
            'latitude' => (float) $bem2->latitude,
            'longitude' => (float) $bem2->longitude,
            'nome_bem' => $bem2->nome_bem,
            'natureza_bem' => $bem2->natureza?->value ?? $bem2->natureza,
            'tipo_bem' => $bem2->tipo?->value ?? $bem2->tipo,
            'uf' => $bem2->uf,
            'artefatos' => $bem2->artefatos ?? [],
            'status_sincronizacao' => 'pendente',
            'versao' => 4,
            'dados_coletados' => [
                'nomes_populares' => 'Pedra Furada — Toca da Onça Pintada',
                'descricao_atualizacao' => 'Revisão de 2026 identificou nível de ocupação adicional datado de '
                    .'32.000 AP via OSL. Pinturas rupestres no painel sul reclassificadas como Tradição '
                    .'Nordeste, fase Agreste.',
                'midias' => [['tipo' => 'artigo', 'url' => 'https://doi.org/10.1590/pedra-furada-osl-2026']],
                'responsavel' => ['nome' => 'Equipe de Revisão IPHAN', 'telefone' => '86991230031'],
            ],
        ]);

        Curadoria::create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta2->id,
            'bem_material_id' => $bem2->id,
            'usuario_id' => $coletor->id,
            'status' => 'pendente',
            'acao_resultante' => null,
            'data_avaliacao' => null,
            'observacao' => null,
        ]);

        $this->command->info("Criada curadoria pendente (atualizarSitio) → {$bem2->codigo_iphan}: nomes_populares + descricao_atualizacao");

        // ── 3. PI-BASE-0003: meios_acesso (valor→novo valor) + artefato novo ────
        $bem3 = $bens['PI-BASE-0003'];
        $artefatosNovos = array_values(array_unique(array_merge($bem3->artefatos ?? [], ['carvao'])));

        $coleta3 = $this->criarColeta([
            'usuario_id' => $coletor->id,
            'data_coleta' => Carbon::now(),
            'latitude' => (float) $bem3->latitude,
            'longitude' => (float) $bem3->longitude,
            'nome_bem' => $bem3->nome_bem,
            'natureza_bem' => $bem3->natureza?->value ?? $bem3->natureza,
            'tipo_bem' => $bem3->tipo?->value ?? $bem3->tipo,
            'uf' => $bem3->uf,
            'artefatos' => $artefatosNovos,
            'status_sincronizacao' => 'pendente',
            'versao' => 3,
            'dados_coletados' => [
                'meios_acesso' => 'Acesso pela PI-111 até Piracuruca, nova portaria inaugurada em mar/2026 '
                    .'na km 312 do PARNA Sete Cidades. Guia credenciado obrigatório. '
                    .'Agendamento pelo app ICMBIO. Trilha acessível para cadeirantes no trecho sul.',
                'artefatos' => $artefatosNovos,
                'midias' => [['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/sete-cidades-acessibilidade.jpg']],
                'responsavel' => ['nome' => 'Msc. Ana Paula Sousa', 'telefone' => '86327644550'],
            ],
        ]);

        Curadoria::create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta3->id,
            'bem_material_id' => $bem3->id,
            'usuario_id' => $coletor->id,
            'status' => 'pendente',
            'acao_resultante' => null,
            'data_avaliacao' => null,
            'observacao' => null,
        ]);

        $this->command->info("Criada curadoria pendente (atualizarSitio) → {$bem3->codigo_iphan}: meios_acesso + artefatos");

        $this->command->info('CuradoriaAtualizacaoPendenteSeeder: 3 curadorias pendentes inseridas.');
    }
}
