<?php

namespace Database\Factories;

use App\Enums\ArtefatoBem;
use App\Enums\NaturezaBem;
use App\Enums\TipoBem;
use App\Models\BemMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<BemMaterial>
 */
class BemMaterialFactory extends Factory
{
    protected $model = BemMaterial::class;

    /** Sítios reais do Piauí com coordenadas precisas. */
    private static array $sitiosPiaui = [
        [
            'nome' => 'Sítio Boqueirão da Pedra Furada',
            'municipio' => 'São Raimundo Nonato',
            'lat' => -8.4823,
            'lng' => -42.6065,
            'iphan' => 'PI-0001',
        ],
        [
            'nome' => 'Toca do Boqueirão do Sítio da Pedra Furada',
            'municipio' => 'São Raimundo Nonato',
            'lat' => -8.5012,
            'lng' => -42.5891,
            'iphan' => 'PI-0002',
        ],
        [
            'nome' => 'Sítio das Pinturas das Sete Cidades',
            'municipio' => 'Piracuruca',
            'lat' => -4.0951,
            'lng' => -41.6952,
            'iphan' => 'PI-0003',
        ],
        [
            'nome' => 'Abrigo do Cânion do Rio Poti',
            'municipio' => 'Teresina',
            'lat' => -5.0921,
            'lng' => -42.8016,
            'iphan' => 'PI-0004',
        ],
        [
            'nome' => 'Sítio das Nascentes do Parnaíba',
            'municipio' => 'Buriti dos Montes',
            'lat' => -10.1243,
            'lng' => -44.9821,
            'iphan' => 'PI-0005',
        ],
        [
            'nome' => 'Toca do Cosmos',
            'municipio' => 'São Raimundo Nonato',
            'lat' => -8.5142,
            'lng' => -42.5876,
            'iphan' => 'PI-0006',
        ],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sitio = fake()->randomElement(self::$sitiosPiaui);
        $lat = round($sitio['lat'] + fake()->randomFloat(5, -0.02, 0.02), 7);
        $lng = round($sitio['lng'] + fake()->randomFloat(5, -0.02, 0.02), 7);
        $seq = fake()->unique()->numerify('####');

        return [
            'coleta_id' => null,
            'codigo_iphan' => $sitio['iphan'].'-'.$seq,
            'nome_bem' => $sitio['nome'],
            'nomes_populares' => fake()->optional(0.6)->words(3, true),
            'natureza' => NaturezaBem::ARQUEOLOGICO,
            'tipo' => TipoBem::SITIO,
            'meios_acesso' => 'Acesso por trilha de terra, '.fake()->numberBetween(5, 30).' km da sede municipal.',
            'artefatos' => fake()->randomElements(
                array_column(ArtefatoBem::cases(), 'value'),
                fake()->numberBetween(1, 4),
            ),
            'publicado' => false,
            'uf' => 'PI',
            'municipio' => $sitio['municipio'],
            'cep' => '64'.fake()->numerify('###').'-'.fake()->numerify('###'),
            'endereco' => 'Zona rural — '.$sitio['municipio'].', PI',
            'latitude' => $lat,
            'longitude' => $lng,
            'geojson' => ['type' => 'Point', 'coordinates' => [$lng, $lat]],
            'ano_registro' => fake()->numberBetween(1990, 2024),
            'descricao_atualizacao' => fake()->paragraph(2),
        ];
    }

    /** Aplica geom PostGIS após persistência. */
    public function configure(): static
    {
        return $this->afterCreating(function (BemMaterial $bem) {
            if ($bem->latitude === null || $bem->longitude === null) {
                return;
            }

            DB::statement(
                'UPDATE bens_materiais
                 SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326)
                 WHERE id = ?',
                [$bem->longitude, $bem->latitude, $bem->id]
            );
        });
    }

    public function publicado(): static
    {
        return $this->state(fn () => ['publicado' => true]);
    }

    public function naoPublicado(): static
    {
        return $this->state(fn () => ['publicado' => false]);
    }

    public function sitio(): static
    {
        return $this->state(fn () => [
            'natureza' => NaturezaBem::ARQUEOLOGICO,
            'tipo' => TipoBem::SITIO,
        ]);
    }

    public function paleontologico(): static
    {
        return $this->state(fn () => [
            'natureza' => NaturezaBem::PALEONTOLOGICO,
        ]);
    }

    public function comArtefatos(array $artefatos): static
    {
        return $this->state(fn () => ['artefatos' => $artefatos]);
    }

    public function localizadoEm(float $latitude, float $longitude): static
    {
        return $this->state(fn () => [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'geojson' => ['type' => 'Point', 'coordinates' => [$longitude, $latitude]],
        ]);
    }

    public function pertoDe(float $latitude, float $longitude, float $delta = 0.01): static
    {
        $lat = fake()->randomFloat(6, $latitude - $delta, $latitude + $delta);
        $lng = fake()->randomFloat(6, $longitude - $delta, $longitude + $delta);

        return $this->state(fn () => [
            'latitude' => $lat,
            'longitude' => $lng,
            'geojson' => ['type' => 'Point', 'coordinates' => [$lng, $lat]],
        ]);
    }
}
