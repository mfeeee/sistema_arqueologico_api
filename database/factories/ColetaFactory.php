<?php

namespace Database\Factories;

use App\Enums\NaturezaBem;
use App\Enums\StatusColeta;
use App\Models\Coleta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coleta>
 */
class ColetaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'data_coleta' => fake()->dateTimeBetween('-1 year', 'now'),
            'latitude' => fake()->latitude(-33, 5),
            'longitude' => fake()->longitude(-73, -34),
            'nome_bem' => fake()->words(3, true),
            'natureza_bem' => NaturezaBem::ARQUEOLOGICO,
            'tipo_bem' => null,
            'artefatos' => [],
            'status_sync' => StatusColeta::PENDENTE,
            'uf' => null,
            'versao' => 1,
            'dados_coletados' => [],
            'deletado_em' => null,
        ];
    }
}
