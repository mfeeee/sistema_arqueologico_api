<?php

namespace Database\Factories;

use App\Enums\TipoMidia;
use App\Models\BemMaterial;
use App\Models\Coleta;
use App\Models\Midia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Midia>
 */
class MidiaFactory extends Factory
{
    protected $model = Midia::class;

    private static array $mimeTypes = [
        TipoMidia::IMAGEM->value => ['image/jpeg', 'image/png', 'image/webp'],
        TipoMidia::VIDEO->value => ['video/mp4', 'video/quicktime'],
        TipoMidia::TESE->value => ['application/pdf'],
        TipoMidia::ARTIGO->value => ['application/pdf'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tipo = fake()->randomElement(TipoMidia::cases());
        $mimes = self::$mimeTypes[$tipo->value];

        return [
            'mediable_type' => BemMaterial::class,
            'mediable_id' => BemMaterial::factory(),
            'storage_disk' => 's3',
            'storage_path' => 'midias/'.fake()->uuid().'/'.$this->nomeArquivo($tipo),
            'mime_type' => fake()->randomElement($mimes),
            'tipo' => $tipo,
            'descricao' => fake()->optional(0.6)->sentence(),
        ];
    }

    public function paraColeta(): static
    {
        return $this->state(fn () => [
            'mediable_type' => Coleta::class,
            'mediable_id' => Coleta::factory(),
        ]);
    }

    public function paraBemMaterial(): static
    {
        return $this->state(fn () => [
            'mediable_type' => BemMaterial::class,
            'mediable_id' => BemMaterial::factory(),
        ]);
    }

    public function imagem(): static
    {
        return $this->state(fn () => [
            'tipo' => TipoMidia::IMAGEM,
            'mime_type' => 'image/jpeg',
            'storage_path' => 'midias/'.fake()->uuid().'/foto.jpg',
        ]);
    }

    private function nomeArquivo(TipoMidia $tipo): string
    {
        return match ($tipo) {
            TipoMidia::IMAGEM => fake()->slug(2).'.jpg',
            TipoMidia::VIDEO => fake()->slug(2).'.mp4',
            TipoMidia::TESE, TipoMidia::ARTIGO => fake()->slug(3).'.pdf',
        };
    }
}
