<?php

namespace Database\Factories;

use App\Enums\AcaoResultanteCuradoria;
use App\Enums\StatusCuradoria;
use App\Models\BemMaterial;
use App\Models\Coleta;
use App\Models\Curadoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Curadoria>
 */
class CuradoriaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entidade_tipo' => 'coleta',
            'entidade_id' => Coleta::factory(),
            'bem_material_id' => BemMaterial::factory(),
            'usuario_id' => User::factory(),
            'status' => StatusCuradoria::PENDENTE,
            'acao_resultante' => AcaoResultanteCuradoria::CRIAR_SITIO,
            'data_avaliacao' => now(),
            'observacao' => fake()->sentence(5),
        ];
    }
}
