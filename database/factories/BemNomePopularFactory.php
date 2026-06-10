<?php

namespace Database\Factories;

use App\Models\BemMaterial;
use App\Models\BemNomePopular;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BemNomePopular>
 */
class BemNomePopularFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bem_material_id' => BemMaterial::factory(),
            'nome' => fake()->words(2, true),
        ];
    }
}
