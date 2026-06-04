<?php

namespace Database\Factories;

use App\Models\Localizacao;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Localizacao>
 */
class LocalizacaoFactory extends Factory
{
    private static array $locaisPiaui = [
        ['municipio' => 'São Raimundo Nonato', 'uf' => 'PI', 'lat' => -8.4823, 'lng' => -42.6065],
        ['municipio' => 'Piracuruca',           'uf' => 'PI', 'lat' => -4.0951, 'lng' => -41.6952],
        ['municipio' => 'Teresina',             'uf' => 'PI', 'lat' => -5.0921, 'lng' => -42.8016],
        ['municipio' => 'Buriti dos Montes',    'uf' => 'PI', 'lat' => -10.1243, 'lng' => -44.9821],
        ['municipio' => 'Caracol',              'uf' => 'PI', 'lat' => -9.2834, 'lng' => -43.3142],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $local = fake()->randomElement(self::$locaisPiaui);

        return [
            'cep' => '64'.fake()->numerify('###').'-'.fake()->numerify('###'),
            'logradouro' => 'Zona rural — '.$local['municipio'].', '.$local['uf'],
            'municipio' => $local['municipio'],
            'uf' => $local['uf'],
        ];
    }

    public function comGeom(float $latitude, float $longitude): static
    {
        return $this->afterCreating(function (Localizacao $localizacao) use ($latitude, $longitude) {
            DB::statement(
                'UPDATE localizacoes SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
                [$longitude, $latitude, $localizacao->id]
            );
        });
    }
}
