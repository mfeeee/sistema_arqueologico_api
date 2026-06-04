<?php

namespace Tests\Feature;

use App\Models\ArtefatoTipo;
use App\Models\BemArtefatoTipo;
use App\Models\BemMaterial;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BemArtefatoTipoTest extends TestCase
{
    use RefreshDatabase;

    public function test_pode_vincular_bem_material_a_artefato_tipo(): void
    {
        $vinculo = BemArtefatoTipo::factory()->create();

        $this->assertDatabaseHas('bem_artefato_tipos', [
            'id' => $vinculo->id,
            'novo_tipo' => false,
            'descricao_nova' => null,
        ]);
    }

    public function test_state_novo_tipo_preenche_campos_corretamente(): void
    {
        $vinculo = BemArtefatoTipo::factory()
            ->novoTipo('Concha perfurada para adorno')
            ->create();

        $this->assertDatabaseHas('bem_artefato_tipos', [
            'id' => $vinculo->id,
            'novo_tipo' => true,
            'descricao_nova' => 'Concha perfurada para adorno',
        ]);
    }

    public function test_nao_permite_duplicata_de_bem_material_e_artefato_tipo(): void
    {
        $bem = BemMaterial::factory()->create();
        $artefatoTipo = ArtefatoTipo::factory()->create();

        BemArtefatoTipo::factory()->create([
            'bem_material_id' => $bem->id,
            'artefato_tipo_id' => $artefatoTipo->id,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        BemArtefatoTipo::factory()->create([
            'bem_material_id' => $bem->id,
            'artefato_tipo_id' => $artefatoTipo->id,
        ]);
    }

    public function test_relacao_bem_material_retorna_artefato_tipos(): void
    {
        $bem = BemMaterial::factory()->create();
        BemArtefatoTipo::factory()->count(2)->create(['bem_material_id' => $bem->id]);

        $this->assertCount(2, $bem->artefatoTipos);
    }

    public function test_relacao_artefato_tipo_retorna_bem_artefato_tipos(): void
    {
        $artefatoTipo = ArtefatoTipo::factory()->create();
        BemArtefatoTipo::factory()->count(3)->create(['artefato_tipo_id' => $artefatoTipo->id]);

        $this->assertCount(3, $artefatoTipo->bemArtefatoTipos);
    }

    public function test_cascade_deleta_vinculos_ao_remover_bem_material(): void
    {
        $vinculo = BemArtefatoTipo::factory()->create();
        $bemId = $vinculo->bem_material_id;

        $vinculo->bemMaterial->forceDelete();

        $this->assertDatabaseMissing('bem_artefato_tipos', ['bem_material_id' => $bemId]);
    }
}
