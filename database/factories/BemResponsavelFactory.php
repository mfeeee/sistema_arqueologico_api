<?php

namespace Database\Factories;

use App\Enums\PapelResponsavelBem;
use App\Models\BemMaterial;
use App\Models\BemResponsavel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BemResponsavel>
 */
class BemResponsavelFactory extends Factory
{
    protected $model = BemResponsavel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bem_material_id' => BemMaterial::factory(),
            'user_id' => User::factory(),
            'papel' => fake()->randomElement(PapelResponsavelBem::cases()),
        ];
    }

    public function pesquisador(): static
    {
        return $this->state(fn () => ['papel' => PapelResponsavelBem::PESQUISADOR]);
    }

    public function curador(): static
    {
        return $this->state(fn () => ['papel' => PapelResponsavelBem::CURADOR]);
    }

    public function responsavelTecnico(): static
    {
        return $this->state(fn () => ['papel' => PapelResponsavelBem::RESPONSAVEL_TECNICO]);
    }
}
