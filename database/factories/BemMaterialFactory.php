<?php

namespace Database\Factories;

use App\Enums\ArtefatoBem;
use App\Enums\NaturezaBem;
use App\Enums\TipoBem;
use App\Models\BemMaterial;
use BcMath\Number;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<BemMaterial>
 */
class BemMaterialFactory extends Factory
{
    protected $model = BemMaterial::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome_bem' => fake()->words(3, true),
            'nomes_populares' => fake()->words(3, true),
            'natureza' =>  NaturezaBem::ARQUEOLOGICO,
            'tipo' => TipoBem::SITIO,
            'meios_acesso' => fake()->sentences(2, true),
            'artefatos' => fake()->randomElements(
                array_map(
                    fn (ArtefatoBem $artefato) => $artefato->name,
                    ArtefatoBem::cases()
                ),
                fake()->numberBetween(1, count(ArtefatoBem::cases()))
            ),
            'publicado' => false,
            'uf' => fake()->randomElement(['PI', 'MA', 'CE', 'PA']),
            'municipio' => fake()->city(),
            'cep' => fake()->numerify('########'),
            'endereco' => fake()->streetAddress(),
            'latitude' => (float) fake()->latitude(-10, 5),
            'longitude' => (float) fake()->longitude(-50, -35),
            'geojson' => null,
            'ano_registro' => null,
            'descricao_atualizacao' => null,
        ];
        
    }

    public function configure(): static
    {
        return $this->afterCreating(function (BemMaterial $bem) {
            if ($bem->latitude === null || $bem->longitude === null) {
                return;
            }

            DB::statement(
                'UPDATE bens_materiais
                 SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326)
                 WHERE id = ?',
                [$bem->longitude, $bem->latitude, $bem->id]
            );
        });
    }

    public function publicado(): static
    {
        return $this->state(fn () => [
            'publicado' => true,
        ]);
    }

    public function naoPublicado(): static
    {
        return $this->state(fn () => [
            'publicado' => false,
        ]);
    }

    public function sitio(): static
    {
        return $this->state(fn () => [
            'natureza' => NaturezaBem::ARQUEOLOGICO,
            'tipo' => TipoBem::SITIO,
        ]);
    }

    public function artefato(array $artefatos = [ArtefatoBem::LITICO]): static
    {
        return $this->state(fn () => [
            'artefatos' => $artefatos,
        ]);
    }

    public function localizadoEm(float $latitude, float $longitude): static
    {
        return $this->state(fn () => [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    public function pertoDe(
        float $latitude,
        float $longitude,
        float $delta = 0.01
    ): static {
        return $this->state(fn () => [
            'latitude' => fake()->randomFloat(6, $latitude - $delta, $latitude + $delta),
            'longitude' => fake()->randomFloat(6, $longitude - $delta, $longitude + $delta),
        ]);
    }
}
