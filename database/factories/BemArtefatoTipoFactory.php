<?php

namespace Database\Factories;

use App\Models\ArtefatoTipo;
use App\Models\BemArtefatoTipo;
use App\Models\BemMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BemArtefatoTipo>
 */
class BemArtefatoTipoFactory extends Factory
{
    protected $model = BemArtefatoTipo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bem_material_id' => BemMaterial::factory(),
            'artefato_tipo_id' => ArtefatoTipo::factory(),
            'descricao_nova' => null,
            'novo_tipo' => false,
        ];
    }

    public function novoTipo(string $descricao): static
    {
        return $this->state(fn () => [
            'novo_tipo' => true,
            'descricao_nova' => $descricao,
        ]);
    }
}
