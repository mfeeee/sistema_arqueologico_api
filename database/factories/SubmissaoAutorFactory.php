<?php

namespace Database\Factories;

use App\Models\SubmissaoArtigo;
use App\Models\SubmissaoAutor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissaoAutor>
 */
class SubmissaoAutorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submissao_id' => SubmissaoArtigo::factory(),
            'nome_autor' => fake()->name(),
            'ordem' => 0,
        ];
    }
}
