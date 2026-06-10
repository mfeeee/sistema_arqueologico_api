<?php

namespace Database\Factories;

use App\Models\ArtigoAutor;
use App\Models\ArtigoCientifico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArtigoAutor>
 */
class ArtigoAutorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'artigo_id' => ArtigoCientifico::factory(),
            'nome_autor' => fake()->name(),
            'ordem' => 0,
        ];
    }
}
