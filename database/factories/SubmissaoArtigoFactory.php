<?php

namespace Database\Factories;

use App\Enums\TipoMencaoArtigo;
use App\Models\BemMaterial;
use App\Models\SubmissaoArtigo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissaoArtigo>
 */
class SubmissaoArtigoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'bem_material_id' => BemMaterial::factory(),
            'artigo_id' => null,
            'doi' => fake()->unique()->numerify('10.####/####-####'),
            'titulo' => fake()->sentence(6),
            'ano_publicacao' => fake()->numberBetween(1990, 2025),
            'periodico' => fake()->company(),
            'idioma' => 'pt',
            'resumo' => fake()->paragraph(),
            'link_acesso' => fake()->optional()->url(),
            'tipo_mencao' => TipoMencaoArtigo::CITACAO,
            'trecho_relevante' => fake()->sentence(),
            'status' => 'pendente',
        ];
    }

    public function aprovado(): static
    {
        return $this->state(fn () => ['status' => 'aprovado']);
    }

    public function rejeitado(): static
    {
        return $this->state(fn () => ['status' => 'rejeitado']);
    }
}
