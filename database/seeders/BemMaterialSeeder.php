<?php

namespace Database\Seeders;

use App\Models\BemMaterial;
use App\Models\MidiaLink;
use App\Models\ResponsavelSitio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Cria 6 bens materiais base localizados no Piauí.
 * • 3 com publicado = true  (código PI-BASE-0001 a 0003)
 * • 3 com publicado = false (código PI-BASE-0004 a 0006)
 *
 * Cada bem recebe 2 midias_links e 1 responsavel_sitio.
 */
class BemMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $bens = [
            // ── PUBLICADOS ───────────────────────────────────────────────────────
            [
                'codigo_iphan' => 'PI-BASE-0001',
                'nome_bem' => 'Sítio Boqueirão da Pedra Furada',
                'nomes_populares' => 'Pedra Furada',
                'natureza' => 'bemArqueologico',
                'tipo' => 'sitio',
                'meios_acesso' => 'Acesso pela BR-020 até São Raimundo Nonato; trilha de 8 km ao sítio.',
                'artefatos' => ['litico', 'ceramica', 'carvao'],
                'publicado' => true,
                'uf' => 'PI',
                'municipio' => 'São Raimundo Nonato',
                'cep' => '64770-000',
                'endereco' => 'Parque Nacional Serra da Capivara, área 1',
                'latitude' => -8.4823,
                'longitude' => -42.6065,
                'ano_registro' => 1973,
                'descricao_atualizacao' => 'Sítio rupestre com mais de 1.200 pinturas. Patrimônio Mundial UNESCO (1991).',
                'midias' => [
                    ['tipo' => 'imagem',  'url' => 'https://arqueologia.example.com/fotos/pedra-furada-panorama.jpg',    'descricao' => 'Vista panorâmica do abrigo'],
                    ['tipo' => 'artigo',  'url' => 'https://doi.org/10.1590/0101-4714.2023.001',                          'descricao' => 'Datação radiocarbônica revisada'],
                ],
                'responsavel' => [
                    'contato_nome' => 'Dra. Niède Guidon',
                    'contato_email' => 'guidon@fumdham.org.br',
                    'contato_telefone' => '(86) 3582-1388',
                ],
            ],
            [
                'codigo_iphan' => 'PI-BASE-0002',
                'nome_bem' => 'Toca do Boqueirão do Sítio da Pedra Furada',
                'nomes_populares' => 'Toca dos Cabloquinhos',
                'natureza' => 'bemArqueologico',
                'tipo' => 'sitio',
                'meios_acesso' => 'Acesso monitorado pelo Parque Nacional Serra da Capivara, guia obrigatório.',
                'artefatos' => ['litico', 'ossosFaunisticos', 'carvao'],
                'publicado' => true,
                'uf' => 'PI',
                'municipio' => 'São Raimundo Nonato',
                'cep' => '64770-000',
                'endereco' => 'Parque Nacional Serra da Capivara, área 2 — trilha norte',
                'latitude' => -8.5012,
                'longitude' => -42.5891,
                'ano_registro' => 1984,
                'descricao_atualizacao' => 'Abrigo com fogueiras pré-históricas datadas de 17.000 AP. Sedimento estratigráfico preservado.',
                'midias' => [
                    ['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/toca-boqueirnao-1.jpg',   'descricao' => 'Painel com figuras antropomorfas'],
                    ['tipo' => 'video',  'url' => 'https://www.youtube.com/watch?v=SERRACAPIVARA2024',              'descricao' => 'Documentário FUMDHAM 2024'],
                ],
                'responsavel' => [
                    'contato_nome' => 'Prof. Eric Boëda',
                    'contato_email' => 'boeda.campo@fumdham.org.br',
                    'contato_telefone' => '(86) 3582-1389',
                ],
            ],
            [
                'codigo_iphan' => 'PI-BASE-0003',
                'nome_bem' => 'Sítio das Pinturas Rupestres de Sete Cidades',
                'nomes_populares' => 'Sete Cidades',
                'natureza' => 'bemArqueologico',
                'tipo' => 'sitio',
                'meios_acesso' => 'Acesso pela PI-111 até Piracuruca; entrada pelo PARNA Sete Cidades.',
                'artefatos' => ['litico', 'ceramica'],
                'publicado' => true,
                'uf' => 'PI',
                'municipio' => 'Piracuruca',
                'cep' => '64260-000',
                'endereco' => 'Parque Nacional Sete Cidades — zona de proteção integral',
                'latitude' => -4.0951,
                'longitude' => -41.6952,
                'ano_registro' => 1961,
                'descricao_atualizacao' => 'Formações rochosas com pinturas geométricas e zoomorfas. Área protegida pelo IBAMA.',
                'midias' => [
                    ['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/sete-cidades-formacao.jpg',  'descricao' => 'Formação rochosa 7 — painel principal'],
                    ['tipo' => 'artigo', 'url' => 'https://doi.org/10.1590/0101-4714.2022.sete',                       'descricao' => 'Análise iconográfica dos painéis'],
                ],
                'responsavel' => [
                    'contato_nome' => 'Msc. Ana Paula Sousa',
                    'contato_email' => 'anapaula@icmbio.gov.br',
                    'contato_telefone' => '(86) 3276-4455',
                ],
            ],

            // ── NÃO PUBLICADOS ────────────────────────────────────────────────────
            // Campos intencionalmente nulos para servir de alvo do Cenário C
            // (atualizarSitio preenchendo campo que estava null).
            [
                'codigo_iphan' => 'PI-BASE-0004',
                'nome_bem' => 'Abrigo do Cânion do Rio Poti',
                'nomes_populares' => null, // ← será preenchido no Cenário C1
                'natureza' => 'bemArqueologico',
                'tipo' => 'sitio',
                'meios_acesso' => 'Acesso via APA do Rio Poti; coordenadas necessárias para navegação.',
                'artefatos' => ['litico', 'faianca', 'metalico'],
                'publicado' => false,
                'uf' => 'PI',
                'municipio' => 'Teresina',
                'cep' => '64001-000',
                'endereco' => 'Área de Proteção Ambiental do Rio Poti — setor leste',
                'latitude' => -5.0921,
                'longitude' => -42.8016,
                'ano_registro' => 2018,
                'descricao_atualizacao' => 'Sítio descoberto em levantamento de 2018. Documentação em andamento.',
                'midias' => [
                    ['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/canion-poti-entrada.jpg',  'descricao' => 'Entrada do abrigo — visão geral'],
                    ['tipo' => 'tese',   'url' => 'https://repositorio.ufpi.br/teses/poti-canion-2019',              'descricao' => 'Relatório de prospecção 2019'],
                ],
                'responsavel' => [
                    'contato_nome' => 'Dr. Roberto Lima',
                    'contato_email' => 'robertolima@ufpi.edu.br',
                    'contato_telefone' => '(86) 3215-9900',
                ],
            ],
            [
                'codigo_iphan' => 'PI-BASE-0005',
                'nome_bem' => 'Sítio das Nascentes do Rio Parnaíba',
                'nomes_populares' => 'Nascentes do Parnaíba',
                'natureza' => 'bemPaleontologico',
                'tipo' => 'sitio',
                'meios_acesso' => null, // ← será preenchido no Cenário C2
                'artefatos' => ['sedimento', 'ossosFaunisticos', 'litico'],
                'publicado' => false,
                'uf' => 'PI',
                'municipio' => 'Buriti dos Montes',
                'cep' => '64660-000',
                'endereco' => 'Parque Nacional das Nascentes do Rio Parnaíba — bloco A',
                'latitude' => -10.1243,
                'longitude' => -44.9821,
                'ano_registro' => 2015,
                'descricao_atualizacao' => 'Afloramento com material ósseo de megafauna pleistocênica. Em análise laboratorial.',
                'midias' => [
                    ['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/nascentes-parnaiba-escavacao.jpg', 'descricao' => 'Escavação nível 3 — fragmentos ósseos'],
                    ['tipo' => 'artigo', 'url' => 'https://doi.org/10.1590/0101-4714.2025.nascentes',                       'descricao' => 'Pré-publicação: fauna pleistocênica do Piauí'],
                ],
                'responsavel' => [
                    'contato_nome' => 'Dra. Camila Nogueira',
                    'contato_email' => 'camila.geo@ufpi.edu.br',
                    'contato_telefone' => '(86) 9 9812-3456',
                ],
            ],
            [
                'codigo_iphan' => 'PI-BASE-0006',
                'nome_bem' => 'Toca do Cosmos',
                'nomes_populares' => 'Cosmos — Abrigo da Anta',
                'natureza' => 'bemArqueologico',
                'tipo' => 'acervoOuColecao',
                'meios_acesso' => 'Trilha restrita; acesso apenas com pesquisadores credenciados pela FUMDHAM.',
                'artefatos' => ['ceramica', 'litico', 'textil'],
                'publicado' => false,
                'uf' => 'PI',
                'municipio' => null, // ← será preenchido no Cenário C3
                'cep' => null, // ← será preenchido no Cenário C3
                'endereco' => 'Parque Nacional Serra da Capivara — área 3, trilha do Cosmos',
                'latitude' => -8.5142,
                'longitude' => -42.5876,
                'ano_registro' => 2009,
                'descricao_atualizacao' => 'Pequeno abrigo com acervo de fragmentos têxteis pré-históricos. Único no Nordeste.',
                'midias' => [
                    ['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/toca-cosmos-texteis.jpg',  'descricao' => 'Fragmentos têxteis in situ'],
                    ['tipo' => 'tese',   'url' => 'https://repositorio.ufpi.br/teses/cosmos-texteis-2010',           'descricao' => 'Dissertação: têxteis pré-históricos da Serra da Capivara'],
                ],
                'responsavel' => [
                    'contato_nome' => 'Dr. François Parenti',
                    'contato_email' => 'parenti@fumdham.org.br',
                    'contato_telefone' => '(86) 3582-1390',
                ],
            ],
        ];

        foreach ($bens as $dados) {
            $bem = BemMaterial::updateOrCreate(
                ['codigo_iphan' => $dados['codigo_iphan']],
                [
                    'codigo_iphan' => $dados['codigo_iphan'],
                    'nome_bem' => $dados['nome_bem'],
                    'nomes_populares' => $dados['nomes_populares'],
                    'natureza' => $dados['natureza'],
                    'tipo' => $dados['tipo'],
                    'meios_acesso' => $dados['meios_acesso'],
                    'artefatos' => $dados['artefatos'],
                    'publicado' => $dados['publicado'],
                    'uf' => $dados['uf'],
                    'municipio' => $dados['municipio'],
                    'cep' => $dados['cep'],
                    'endereco' => $dados['endereco'],
                    'latitude' => $dados['latitude'],
                    'longitude' => $dados['longitude'],
                    'geojson' => ['type' => 'Point', 'coordinates' => [$dados['longitude'], $dados['latitude']]],
                    'ano_registro' => $dados['ano_registro'],
                    'descricao_atualizacao' => $dados['descricao_atualizacao'],
                ]
            );

            // Atualiza coluna geoespacial PostGIS.
            DB::statement(
                'UPDATE bens_materiais SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
                [$bem->longitude, $bem->latitude, $bem->id]
            );

            // Remove mídias e responsável existentes antes de recriar (idempotência).
            $bem->midias()->delete();
            $bem->responsaveis()->delete();

            foreach ($dados['midias'] as $midia) {
                MidiaLink::create([
                    'bem_material_id' => $bem->id,
                    'tipo' => $midia['tipo'],
                    'url' => $midia['url'],
                    'descricao' => $midia['descricao'],
                ]);
            }

            ResponsavelSitio::create([
                'bem_material_id' => $bem->id,
                'contato_nome' => $dados['responsavel']['contato_nome'],
                'contato_email' => $dados['responsavel']['contato_email'],
                'contato_telefone' => $dados['responsavel']['contato_telefone'],
            ]);
        }

        $this->command->info('BemMaterialSeeder: 6 sítios do Piauí criados (3 publicados, 3 não publicados).');
    }
}
