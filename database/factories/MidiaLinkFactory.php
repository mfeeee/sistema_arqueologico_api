<?php

namespace Database\Factories;

use App\Models\BemMaterial;
use App\Models\MidiaLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MidiaLink>
 */
class MidiaLinkFactory extends Factory
{
    protected $model = MidiaLink::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tipo = fake()->randomElement(['imagem', 'video', 'tese', 'artigo']);

        return [
            'bem_material_id' => BemMaterial::factory(),
            'tipo' => $tipo,
            'url' => $this->urlParaTipo($tipo),
            'descricao' => fake()->sentence(6),
        ];
    }

    public function imagem(): static
    {
        return $this->state(fn () => [
            'tipo' => 'imagem',
            'url' => 'https://arqueologia.example.com/fotos/'.fake()->uuid().'.jpg',
        ]);
    }

    public function video(): static
    {
        return $this->state(fn () => [
            'tipo' => 'video',
            'url' => 'https://www.youtube.com/watch?v='.fake()->bothify('??????????'),
        ]);
    }

    public function artigo(): static
    {
        return $this->state(fn () => [
            'tipo' => 'artigo',
            'url' => 'https://doi.org/10.1590/'.fake()->numerify('####.####'),
        ]);
    }

    private function urlParaTipo(string $tipo): string
    {
        return match ($tipo) {
            'imagem' => 'https://arqueologia.example.com/fotos/'.fake()->uuid().'.jpg',
            'video' => 'https://www.youtube.com/watch?v='.fake()->bothify('??????????'),
            'tese' => 'https://repositorio.ufpi.br/teses/'.fake()->numerify('####'),
            'artigo' => 'https://doi.org/10.1590/'.fake()->numerify('####.####'),
            default => fake()->url(),
        };
    }
}
