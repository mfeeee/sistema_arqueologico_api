<?php

namespace Database\Factories;

use App\Models\BemMaterial;
use App\Models\ResponsavelSitio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResponsavelSitio>
 */
class ResponsavelSitioFactory extends Factory
{
    protected $model = ResponsavelSitio::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bem_material_id' => BemMaterial::factory(),
            'contato_nome' => fake()->name(),
            'contato_email' => fake()->safeEmail(),
            'contato_telefone' => '(86) '.fake()->numerify('#####-####'),
        ];
    }

    /** Responsável com telefone de área do Piauí (DDD 86). */
    public function piaui(): static
    {
        return $this->state(fn () => [
            'contato_telefone' => '(86) 9'.fake()->numerify('####-####'),
        ]);
    }
}
