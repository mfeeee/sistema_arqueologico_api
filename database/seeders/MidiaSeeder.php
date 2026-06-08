<?php

namespace Database\Seeders;

use App\Enums\TipoMidia;
use App\Models\BemMaterial;
use App\Models\Coleta;
use App\Models\Midia;
use Illuminate\Database\Seeder;

/**
 * Cria registros de mídias para coletas e bens materiais.
 *
 * • Coletas: extrai URLs de dados_coletados.midias (storage_disk = 'external').
 * • Bens   : recria mídias declaradas por codigo_iphan (idempotente via firstOrCreate).
 *
 * Mídias de bens também são criadas inline no BemMaterialSeeder; este seeder
 * garante cobertura mesmo quando executado isoladamente.
 */
class MidiaSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedMidiasDeColetas();
        $this->seedMidiasDeBens();
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function seedMidiasDeColetas(): void
    {
        $coletas = Coleta::whereNotNull('dados_coletados')->get()
            ->filter(fn (Coleta $c) => ! empty($c->dados_coletados['midias']));

        $total = 0;

        foreach ($coletas as $coleta) {
            foreach ($coleta->dados_coletados['midias'] as $entrada) {
                $tipo = TipoMidia::tryFrom($entrada['tipo'] ?? '') ?? TipoMidia::IMAGEM;

                Midia::firstOrCreate(
                    [
                        'mediable_type' => Coleta::class,
                        'mediable_id' => $coleta->id,
                        'storage_path' => $entrada['url'],
                    ],
                    [
                        'storage_disk' => 'external',
                        'mime_type' => $this->mimeParaTipo($tipo),
                        'tipo' => $tipo,
                        'descricao' => null,
                    ]
                );

                $total++;
            }
        }

        $this->command->info("MidiaSeeder [coletas]: {$total} mídias verificadas/criadas.");
    }

    // ──────────────────────────────────────────────────────────────────────────

    /** @var array<string, list<array{tipo: string, url: string, descricao: string}>> */
    private array $midiasDesBens = [
        'PI-BASE-0001' => [
            ['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/pedra-furada-panorama.jpg',    'descricao' => 'Vista panorâmica do abrigo'],
            ['tipo' => 'artigo', 'url' => 'https://doi.org/10.1590/0101-4714.2023.001',                          'descricao' => 'Datação radiocarbônica revisada'],
        ],
        'PI-BASE-0002' => [
            ['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/toca-boqueirnao-1.jpg',   'descricao' => 'Painel com figuras antropomorfas'],
            ['tipo' => 'video',  'url' => 'https://www.youtube.com/watch?v=SERRACAPIVARA2024',              'descricao' => 'Documentário FUMDHAM 2024'],
        ],
        'PI-BASE-0003' => [
            ['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/sete-cidades-formacao.jpg',  'descricao' => 'Formação rochosa 7 — painel principal'],
            ['tipo' => 'artigo', 'url' => 'https://doi.org/10.1590/0101-4714.2022.sete',                       'descricao' => 'Análise iconográfica dos painéis'],
        ],
        'PI-BASE-0004' => [
            ['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/canion-poti-entrada.jpg',  'descricao' => 'Entrada do abrigo — visão geral'],
            ['tipo' => 'tese',   'url' => 'https://repositorio.ufpi.br/teses/poti-canion-2019',              'descricao' => 'Relatório de prospecção 2019'],
        ],
        'PI-BASE-0005' => [
            ['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/nascentes-parnaiba-escavacao.jpg', 'descricao' => 'Escavação nível 3 — fragmentos ósseos'],
            ['tipo' => 'artigo', 'url' => 'https://doi.org/10.1590/0101-4714.2025.nascentes',                       'descricao' => 'Pré-publicação: fauna pleistocênica do Piauí'],
        ],
        'PI-BASE-0006' => [
            ['tipo' => 'imagem', 'url' => 'https://arqueologia.example.com/fotos/toca-cosmos-texteis.jpg',  'descricao' => 'Fragmentos têxteis in situ'],
            ['tipo' => 'tese',   'url' => 'https://repositorio.ufpi.br/teses/cosmos-texteis-2010',           'descricao' => 'Dissertação: têxteis pré-históricos da Serra da Capivara'],
        ],
    ];

    private function seedMidiasDeBens(): void
    {
        $total = 0;

        foreach ($this->midiasDesBens as $codigoIphan => $midias) {
            $bem = BemMaterial::where('codigo_iphan', $codigoIphan)->first();

            if (! $bem) {
                continue;
            }

            foreach ($midias as $entrada) {
                $tipo = TipoMidia::tryFrom($entrada['tipo']) ?? TipoMidia::IMAGEM;

                Midia::firstOrCreate(
                    [
                        'mediable_type' => BemMaterial::class,
                        'mediable_id' => $bem->id,
                        'storage_path' => $entrada['url'],
                    ],
                    [
                        'storage_disk' => 'external',
                        'mime_type' => $this->mimeParaTipo($tipo),
                        'tipo' => $tipo,
                        'descricao' => $entrada['descricao'],
                    ]
                );

                $total++;
            }
        }

        $this->command->info("MidiaSeeder [bens]: {$total} mídias verificadas/criadas.");
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function mimeParaTipo(TipoMidia $tipo): string
    {
        return match ($tipo) {
            TipoMidia::IMAGEM => 'image/jpeg',
            TipoMidia::VIDEO => 'video/mp4',
            TipoMidia::TESE, TipoMidia::ARTIGO => 'application/pdf',
        };
    }
}
