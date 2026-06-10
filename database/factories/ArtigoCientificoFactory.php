<?php

namespace Database\Factories;

use App\Models\ArtigoCientifico;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArtigoCientifico>
 */
class ArtigoCientificoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'adicionado_por' => User::factory(),
            'titulo' => fake()->sentence(6),
            'doi' => fake()->unique()->numerify('10.####/####-####'),
            'link_acesso' => fake()->optional()->url(),
            'ano_publicacao' => fake()->numberBetween(1990, 2025),
            'periodico' => fake()->company(),
            'idioma' => 'pt',
            'resumo' => fake()->paragraph(),
            'verificado' => false,
        ];
    }

    public function verificado(): static
    {
        return $this->state(fn () => ['verificado' => true]);
    }
}
