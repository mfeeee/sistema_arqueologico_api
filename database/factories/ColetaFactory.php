<?php

namespace Database\Factories;

use App\Enums\ArtefatoBem;
use App\Enums\NaturezaBem;
use App\Enums\StatusColeta;
use App\Enums\TipoBem;
use App\Models\Coleta;
use App\Models\Localizacao;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coleta>
 */
class ColetaFactory extends Factory
{
    protected $model = Coleta::class;

    /** Sítios reais do Piauí para gerar coordenadas realistas. */
    private static array $locaisPiaui = [
        ['lat' => -8.4823, 'lng' => -42.6065, 'municipio' => 'São Raimundo Nonato', 'local' => 'Parque Nacional Serra da Capivara'],
        ['lat' => -4.0951, 'lng' => -41.6952, 'municipio' => 'Piracuruca',           'local' => 'Parque Nacional Sete Cidades'],
        ['lat' => -5.0921, 'lng' => -42.8016, 'municipio' => 'Teresina',             'local' => 'Cânion do Rio Poti'],
        ['lat' => -10.1243, 'lng' => -44.9821, 'municipio' => 'Buriti dos Montes',  'local' => 'Nascentes do Rio Parnaíba'],
        ['lat' => -9.2834, 'lng' => -43.3142,  'municipio' => 'Caracol',             'local' => 'Lagoa de Peixe'],
        ['lat' => -8.5142, 'lng' => -42.5876,  'municipio' => 'São Raimundo Nonato', 'local' => 'Toca do Cosmos'],
    ];

    /** Nomes de bens reais para o Piauí. */
    private static array $nomesBens = [
        'Sítio Boqueirão da Pedra Furada',
        'Toca do Boqueirão do Sítio da Pedra Furada',
        'Abrigo do Perna',
        'Sítio do Meio',
        'Toca do Barrigudo',
        'Sítio Toca das Moendas',
        'Abrigo Pedra Furada',
        'Toca do Cosmos',
        'Toca do Índio Pintado',
        'Sítio das Gravuras do Rio Poti',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $local = fake()->randomElement(self::$locaisPiaui);
        $nome = fake()->randomElement(self::$nomesBens).' — '.$local['local'];
        $lat = round($local['lat'] + fake()->randomFloat(4, -0.05, 0.05), 7);
        $lng = round($local['lng'] + fake()->randomFloat(4, -0.05, 0.05), 7);

        return [
            'usuario_id' => User::factory(),
            'data_coleta' => fake()->dateTimeBetween('-6 months', 'now'),
            'latitude' => $lat,
            'longitude' => $lng,
            'nome_bem' => $nome,
            'natureza_bem' => NaturezaBem::ARQUEOLOGICO,
            'tipo_bem' => fake()->randomElement(TipoBem::cases()),
            'uf' => 'PI',
            'artefatos' => fake()->randomElements(
                array_column(ArtefatoBem::cases(), 'value'),
                fake()->numberBetween(1, 3),
            ),
            'status_sincronizacao' => StatusColeta::PENDENTE,
            'versao' => 1,
            'dados_coletados' => [
                'midias' => [
                    [
                        'tipo' => 'imagem',
                        'url' => 'https://arqueologia.example.com/imagens/'.Str::uuid().'.jpg',
                    ],
                ],
                'responsavel' => [
                    'nome' => fake()->name(),
                    'telefone' => '86'.fake()->numerify('#########'),
                ],
            ],
            'deletado_em' => null,
        ];
    }

    public function pendente(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_sincronizacao' => StatusColeta::PENDENTE,
        ]);
    }

    public function sincronizado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_sincronizacao' => StatusColeta::SINCRONIZADO,
        ]);
    }

    public function comConflito(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_sincronizacao' => StatusColeta::CONFLITO,
            'versao' => fake()->numberBetween(2, 5),
        ]);
    }

    public function comLocalizacao(): static
    {
        return $this->state(fn () => [
            'localizacao_id' => Localizacao::factory(),
        ]);
    }
}
