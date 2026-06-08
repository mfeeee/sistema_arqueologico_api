<?php

namespace Database\Seeders;

use App\Models\ArtigoAutor;
use App\Models\ArtigoBemMaterial;
use App\Models\ArtigoCientifico;
use App\Models\Auditoria;
use App\Models\BemMaterial;
use App\Models\Curadoria;
use App\Models\SubmissaoArtigo;
use App\Models\SubmissaoAutor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Popula cenários de artigos científicos para teste.
 *
 * ┌──────────┬──────────────────────────────────────────────────────────────────────┐
 * │ Cenário  │ Descrição                                                            │
 * ├──────────┼──────────────────────────────────────────────────────────────────────┤
 * │ Artigo-A │ 3 artigos JÁ aprovados e vinculados a bens — exibidos na aba Artigos │
 * │          │   ArtigoCientifico + ArtigoBemMaterial + Curadoria aprovada           │
 * │          │   + Auditoria de Inserção em ArtigoBemMaterial                       │
 * ├──────────┼──────────────────────────────────────────────────────────────────────┤
 * │ Artigo-B │ 2 submissões PENDENTES (cenário B: artigo novo — sem artigo_id)      │
 * │          │   SubmissaoArtigo(pendente) + Curadoria(submissao_artigo, pendente)  │
 * │          │   Aparecem na fila de curadoria como "artigo"                        │
 * ├──────────┼──────────────────────────────────────────────────────────────────────┤
 * │ Artigo-C │ 1 submissão PENDENTE (cenário A: artigo já existe — com artigo_id)   │
 * │          │   Reusa o ArtigoCientifico criado em Artigo-A, novo vínculo pedido   │
 * ├──────────┼──────────────────────────────────────────────────────────────────────┤
 * │ Artigo-D │ 1 submissão REJEITADA (para testar histórico)                        │
 * └──────────┴──────────────────────────────────────────────────────────────────────┘
 *
 * Pré-requisitos: UserSeeder e BemMaterialSeeder já executados.
 */
class ArtigoCientificoSeeder extends Seeder
{
    public function run(): void
    {
        $coletor = User::where('email', 'coletor@arqueologia.test')->firstOrFail();
        $curador = User::where('email', 'curador@arqueologia.test')->firstOrFail();
        $admin = User::where('email', 'admin@arqueologia.test')->firstOrFail();

        $bens = BemMaterial::whereIn('codigo_iphan', [
            'PI-BASE-0001',
            'PI-BASE-0002',
            'PI-BASE-0003',
            'PI-BASE-0004',
            'PI-BASE-0005',
        ])->get()->keyBy('codigo_iphan');

        // ════════════════════════════════════════════════════════════════════════
        // ARTIGO-A — Artigos aprovados e já vinculados a bens
        // ════════════════════════════════════════════════════════════════════════
        $this->command->info('Artigo-A: artigos aprovados + vínculos + auditorias...');

        // Cada artigo é submetido por um usuário diferente para popular
        // a aba de colaboradores com múltiplas entradas distintas.
        $artigosA = [
            [
                'doi' => '10.1590/1981-5921.2024.pedrafurada',
                'titulo' => 'Revisão estratigráfica do Sítio Boqueirão da Pedra Furada: novas datações por AMS',
                'autores' => ['Pessis, A.-M.', 'Guidon, N.', 'Boëda, E.'],
                'ano_publicacao' => 2024,
                'periodico' => 'Revista de Arqueologia',
                'idioma' => 'pt',
                'resumo' => 'O presente trabalho apresenta a revisão estratigráfica e novas datações absolutas '
                    .'para os níveis de ocupação do Sítio Boqueirão da Pedra Furada, localizados na '
                    .'Serra da Capivara, Piauí. Os resultados indicam presença humana anterior a 32.000 AP.',
                'link_acesso' => 'https://doi.org/10.1590/1981-5921.2024.pedrafurada',
                'bem_codigo' => 'PI-BASE-0001',
                'tipo_mencao' => 'estudo_aprofundado',
                'trecho_relevante' => 'Os dados de AMS obtidos para o nível 6B confirmam datação de 32.400 ± 800 AP, '
                    .'consistente com os resultados anteriores de TRSL.',
                'submissor' => 'coletor',  // coletor já aparece via coleta (CasoD1) — reforça contagem
                'dias_atras' => 14,
            ],
            [
                'doi' => '10.1590/2176-7912.2023.tocaboqueirnao',
                'titulo' => 'Fogueiras pré-históricas da Toca do Boqueirão do Sítio da Pedra Furada: análise antracológica',
                'autores' => ['Laureano da Rosa, J.', 'Boëda, E.'],
                'ano_publicacao' => 2023,
                'periodico' => 'Fumdhamentos',
                'idioma' => 'pt',
                'resumo' => 'Análise antracológica de 215 fragmentos de carvão provenientes das fogueiras do nível 5 '
                    .'da Toca do Boqueirão, indicando exploração de espécies da caatinga e cerrado entre '
                    .'18.500 e 14.200 AP.',
                'link_acesso' => 'https://www.fundham.org.br/fumdhamentos/2023-tocaboqueirnao',
                'bem_codigo' => 'PI-BASE-0002',
                'tipo_mencao' => 'analise_artefatos',
                'trecho_relevante' => null,
                'submissor' => 'admin',    // admin também contribui via artigo
                'dias_atras' => 12,
            ],
            [
                'doi' => '10.11606/issn.2179-0892.ra.2022.setecidades',
                'titulo' => 'Pinturas rupestres do Parque Nacional Serra das Sete Cidades: levantamento e classificação tradição Nordeste',
                'autores' => ['Guidon, N.', 'Martins, G.R.', 'Vergne, C.'],
                'ano_publicacao' => 2022,
                'periodico' => 'Revista de Antropologia USP',
                'idioma' => 'pt',
                'resumo' => 'Levantamento sistemático de 847 motivos rupestres distribuídos em 23 painéis '
                    .'do PARNA Sete Cidades. Classificação segundo a Tradição Nordeste com identificação '
                    .'de três subfases crono-estilísticas entre 10.000 e 3.500 AP.',
                'link_acesso' => null,
                'bem_codigo' => 'PI-BASE-0003',
                'tipo_mencao' => 'referencia_geografica',
                'trecho_relevante' => 'O sítio PI-BASE-0003 representa o conjunto rupestre mais bem preservado '
                    .'da Tradição Nordeste no estado do Piauí, com 312 motivos documentados.',
                'submissor' => 'curador',  // curador contribui como pesquisador
                'dias_atras' => 10,
            ],
            // Segundo artigo para PI-BASE-0001 — submetido pelo admin.
            // Garante que PI-BASE-0001 apareça com 2 colaboradores distintos na aba.
            [
                'doi' => '10.1590/1981-5921.2024.pedrafurada-paleoambiente',
                'titulo' => 'Paleoambiente da Serra da Capivara no Pleistoceno tardio: análise polínica e sedimentológica',
                'autores' => ['Parenti, F.', 'Guidon, N.'],
                'ano_publicacao' => 2024,
                'periodico' => 'Quaternary Science Reviews',
                'idioma' => 'en',
                'resumo' => 'Pollen and sediment analysis from cores extracted near Pedra Furada reveals '
                    .'a savanna-like environment during 30,000–18,000 BP, consistent with human occupation evidence.',
                'link_acesso' => 'https://doi.org/10.1590/1981-5921.2024.pedrafurada-paleoambiente',
                'bem_codigo' => 'PI-BASE-0001',
                'tipo_mencao' => 'referencia_geografica',
                'trecho_relevante' => 'The paleoenvironmental reconstruction corroborates human presence at '
                    .'Pedra Furada during periods of reduced vegetation cover.',
                'submissor' => 'admin',    // admin adiciona segundo artigo ao mesmo bem
                'dias_atras' => 8,
            ],
        ];

        $primeiroArtigo = null;

        foreach ($artigosA as $dadosArtigo) {
            $bem = $bens[$dadosArtigo['bem_codigo']];

            // Resolve qual usuário faz a submissão
            $submissor = match ($dadosArtigo['submissor']) {
                'admin' => $admin,
                'curador' => $curador,
                default => $coletor,
            };

            $artigo = ArtigoCientifico::create([
                'adicionado_por' => $curador->id,
                'doi' => $dadosArtigo['doi'],
                'titulo' => $dadosArtigo['titulo'],
                'ano_publicacao' => $dadosArtigo['ano_publicacao'],
                'periodico' => $dadosArtigo['periodico'],
                'idioma' => $dadosArtigo['idioma'],
                'resumo' => $dadosArtigo['resumo'],
                'link_acesso' => $dadosArtigo['link_acesso'],
                'verificado' => true,
            ]);

            foreach ($dadosArtigo['autores'] as $ordem => $nomeAutor) {
                ArtigoAutor::create([
                    'artigo_id' => $artigo->id,
                    'nome_autor' => $nomeAutor,
                    'ordem' => $ordem,
                ]);
            }

            if ($primeiroArtigo === null) {
                $primeiroArtigo = $artigo;
            }

            // Submissão aprovada — usuario_id é o submissor real (quem trouxe o artigo)
            $submissao = SubmissaoArtigo::create([
                'usuario_id' => $submissor->id,
                'bem_material_id' => $bem->id,
                'artigo_id' => $artigo->id,
                'doi' => $dadosArtigo['doi'],
                'titulo' => null,
                'ano_publicacao' => $dadosArtigo['ano_publicacao'],
                'periodico' => $dadosArtigo['periodico'],
                'idioma' => $dadosArtigo['idioma'],
                'resumo' => null,
                'link_acesso' => $dadosArtigo['link_acesso'],
                'tipo_mencao' => $dadosArtigo['tipo_mencao'],
                'trecho_relevante' => $dadosArtigo['trecho_relevante'],
                'status' => 'aprovado',
            ]);

            $vinculo = ArtigoBemMaterial::create([
                'artigo_id' => $artigo->id,
                'bem_material_id' => $bem->id,
                'tipo_mencao' => $dadosArtigo['tipo_mencao'],
                'trecho_relevante' => $dadosArtigo['trecho_relevante'],
            ]);

            // Curadoria: usuario_id = quem submeteu (o avaliador seria o curador/admin,
            // mas para fins de colaboradores o campo relevante é submissoes_artigos.usuario_id)
            $curadoria = Curadoria::create([
                'entidade_tipo' => 'submissao_artigo',
                'entidade_id' => $submissao->id,
                'bem_material_id' => $bem->id,
                'usuario_id' => $submissor->id,
                'status' => 'aprovado',
                'acao_resultante' => 'aprovar',
                'data_avaliacao' => Carbon::now()->subDays($dadosArtigo['dias_atras']),
                'observacao' => 'Artigo verificado e vínculo aprovado.',
            ]);

            Auditoria::create([
                'usuario_id' => $curador->id,
                'entidade_tipo' => 'App\\Models\\ArtigoBemMaterial',
                'entidade_id' => $vinculo->id,
                'curadoria_id' => $curadoria->id,
                'operacao' => 'Inserção',
                'meio' => 'Auditoria',
                'data_hora' => Carbon::now()->subDays($dadosArtigo['dias_atras']),
                'valor_anterior' => null,
                'valor_novo' => [
                    'artigo_id' => $artigo->id,
                    'bem_material_id' => $bem->id,
                    'tipo_mencao' => $dadosArtigo['tipo_mencao'],
                ],
            ]);

            $this->command->info(
                "  → Artigo aprovado ({$submissor->email}): \"{$artigo->titulo}\" → {$bem->codigo_iphan}"
            );
        }

        // ════════════════════════════════════════════════════════════════════════
        // ARTIGO-B — Submissões pendentes de ARTIGO NOVO (sem artigo_id)
        // ════════════════════════════════════════════════════════════════════════
        $this->command->info('Artigo-B: submissões pendentes de artigo novo...');

        $submissoesPendentes = [
            [
                'bem_codigo' => 'PI-BASE-0004',
                'doi' => '10.1590/2317-1529.2025.caniondopoti',
                'titulo' => 'Arqueologia do Cânion do Poti: prospecção sistemática e repertório lítico',
                'autores' => ['Fogaça, E.', 'Guidon, N.'],
                'ano_publicacao' => 2025,
                'periodico' => 'Revista Brasileira de Geografia',
                'idioma' => 'pt',
                'resumo' => 'Prospecção sistemática de 40 km² no entorno do Cânion do Poti identificou '
                    .'27 novos pontos com material lítico associados a ocupações entre 8.000 e 4.000 AP.',
                'link_acesso' => 'https://doi.org/10.1590/2317-1529.2025.caniondopoti',
                'tipo_mencao' => 'estudo_aprofundado',
                'trecho_relevante' => 'O Cânion do Poti apresenta condições excepcionais de conservação '
                    .'de artefatos líticos devido ao microambiente úmido criado pela vegetação ripária.',
                'submissor' => 'coletor',
            ],
            [
                'bem_codigo' => 'PI-BASE-0005',
                'doi' => '10.1590/1516-635x.2025.nascentesd',
                'titulo' => 'Fauna pleistocênica das Nascentes do Rio Gurgueia: registro osteológico e contexto paleoclimático',
                'autores' => ['Araújo, A.G.M.', 'Neves, W.A.'],
                'ano_publicacao' => 2025,
                'periodico' => 'Pesquisas: Geociências',
                'idioma' => 'pt',
                'resumo' => 'Análise de 312 fragmentos ósseos coletados no nível B da Nascente do Gurgueia '
                    .'identificou 8 espécies de megafauna pleistocênica, incluindo Eremotherium laurillardi '
                    .'e Toxodon platensis, em contexto de seca severa datado de 12.000 AP.',
                'link_acesso' => null,
                'tipo_mencao' => 'analise_artefatos',
                'trecho_relevante' => null,
                'submissor' => 'curador',  // curador também submete (diversifica fila)
            ],
        ];

        foreach ($submissoesPendentes as $dados) {
            $bem = $bens[$dados['bem_codigo']];

            $submissorB = match ($dados['submissor']) {
                'curador' => $curador,
                'admin' => $admin,
                default => $coletor,
            };

            $submissao = SubmissaoArtigo::create([
                'usuario_id' => $submissorB->id,
                'bem_material_id' => $bem->id,
                'artigo_id' => null,
                'doi' => $dados['doi'],
                'titulo' => $dados['titulo'],
                'ano_publicacao' => $dados['ano_publicacao'],
                'periodico' => $dados['periodico'],
                'idioma' => $dados['idioma'],
                'resumo' => $dados['resumo'],
                'link_acesso' => $dados['link_acesso'],
                'tipo_mencao' => $dados['tipo_mencao'],
                'trecho_relevante' => $dados['trecho_relevante'],
                'status' => 'pendente',
            ]);

            foreach ($dados['autores'] as $ordem => $nomeAutor) {
                SubmissaoAutor::create([
                    'submissao_id' => $submissao->id,
                    'nome_autor' => $nomeAutor,
                    'ordem' => $ordem,
                ]);
            }

            Curadoria::create([
                'entidade_tipo' => 'submissao_artigo',
                'entidade_id' => $submissao->id,
                'bem_material_id' => $bem->id,
                'usuario_id' => $submissorB->id,
                'status' => 'pendente',
                'acao_resultante' => null,
                'data_avaliacao' => null,
                'observacao' => null,
            ]);

            $this->command->info(
                "  → Submissão pendente ({$submissorB->email}): \"{$dados['titulo']}\" para {$bem->codigo_iphan}"
            );
        }

        // ════════════════════════════════════════════════════════════════════════
        // ARTIGO-C — Submissão pendente com ARTIGO JÁ EXISTENTE (cenário A)
        // Reusa o primeiro artigo criado em Artigo-A, pedindo vínculo com outro bem
        // ════════════════════════════════════════════════════════════════════════
        $this->command->info('Artigo-C: submissão pendente com artigo já existente...');

        if ($primeiroArtigo !== null) {
            $bemC = $bens['PI-BASE-0004'];

            $submissaoC = SubmissaoArtigo::create([
                'usuario_id' => $coletor->id,
                'bem_material_id' => $bemC->id,
                'artigo_id' => $primeiroArtigo->id,
                'doi' => $primeiroArtigo->doi,
                'titulo' => null,
                'ano_publicacao' => $primeiroArtigo->ano_publicacao,
                'periodico' => $primeiroArtigo->periodico,
                'idioma' => $primeiroArtigo->idioma,
                'resumo' => null,
                'link_acesso' => $primeiroArtigo->link_acesso,
                'tipo_mencao' => 'citacao',
                'trecho_relevante' => 'A estratigrafia comparada com o Cânion do Poti confirma '
                    .'ocupação contemporânea na margem norte da Serra da Capivara.',
                'status' => 'pendente',
            ]);

            Curadoria::create([
                'entidade_tipo' => 'submissao_artigo',
                'entidade_id' => $submissaoC->id,
                'bem_material_id' => $bemC->id,
                'usuario_id' => $coletor->id,
                'status' => 'pendente',
                'acao_resultante' => null,
                'data_avaliacao' => null,
                'observacao' => null,
            ]);

            $this->command->info("  → Submissão pendente (artigo existente): DOI {$primeiroArtigo->doi} → {$bemC->codigo_iphan}");
        }

        // ════════════════════════════════════════════════════════════════════════
        // ARTIGO-D — Submissão REJEITADA (histórico)
        // ════════════════════════════════════════════════════════════════════════
        $this->command->info('Artigo-D: submissão rejeitada...');

        $bemD = $bens['PI-BASE-0001'];

        $submissaoD = SubmissaoArtigo::create([
            'usuario_id' => $coletor->id,
            'bem_material_id' => $bemD->id,
            'artigo_id' => null,
            'doi' => null,
            'titulo' => 'Novas Evidências Arqueológicas no Nordeste Brasileiro (preprint sem revisão)',
            'ano_publicacao' => 2025,
            'periodico' => 'Preprint',
            'idioma' => 'pt',
            'resumo' => 'Texto sem embasamento metodológico adequado e sem citação de fontes primárias.',
            'link_acesso' => null,
            'tipo_mencao' => 'citacao',
            'trecho_relevante' => null,
            'status' => 'rejeitado',
        ]);

        SubmissaoAutor::create([
            'submissao_id' => $submissaoD->id,
            'nome_autor' => 'Autor Desconhecido',
            'ordem' => 0,
        ]);

        Curadoria::create([
            'entidade_tipo' => 'submissao_artigo',
            'entidade_id' => $submissaoD->id,
            'bem_material_id' => $bemD->id,
            'usuario_id' => $coletor->id,
            'status' => 'rejeitado',
            'acao_resultante' => 'rejeitar',
            'data_avaliacao' => Carbon::now()->subDays(5),
            'observacao' => 'Preprint sem revisão por pares. Artigo não indexado em base reconhecida. Reenviar após publicação.',
        ]);

        $this->command->info('  → Submissão rejeitada registrada.');
        $this->command->info('ArtigoCientificoSeeder: cenários A–D concluídos.');
    }
}
