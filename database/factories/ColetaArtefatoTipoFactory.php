<?php

namespace Database\Factories;

use App\Models\ArtefatoTipo;
use App\Models\Coleta;
use App\Models\ColetaArtefatoTipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ColetaArtefatoTipo>
 */
class ColetaArtefatoTipoFactory extends Factory
{
    protected $model = ColetaArtefatoTipo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'coleta_id' => Coleta::factory(),
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
