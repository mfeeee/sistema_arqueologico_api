<?php

namespace Tests\Feature;

use App\Models\ArtefatoTipo;
use App\Models\Coleta;
use App\Models\ColetaArtefatoTipo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColetaArtefatoTipoTest extends TestCase
{
    use RefreshDatabase;

    public function test_pode_vincular_coleta_a_artefato_tipo(): void
    {
        $vinculo = ColetaArtefatoTipo::factory()->create();

        $this->assertDatabaseHas('coleta_artefato_tipos', [
            'id' => $vinculo->id,
            'novo_tipo' => false,
            'descricao_nova' => null,
        ]);
    }

    public function test_state_novo_tipo_preenche_campos_corretamente(): void
    {
        $vinculo = ColetaArtefatoTipo::factory()
            ->novoTipo('Pedra com entalhes rituais')
            ->create();

        $this->assertDatabaseHas('coleta_artefato_tipos', [
            'id' => $vinculo->id,
            'novo_tipo' => true,
            'descricao_nova' => 'Pedra com entalhes rituais',
        ]);
    }

    public function test_nao_permite_duplicata_de_coleta_e_artefato_tipo(): void
    {
        $coleta = Coleta::factory()->create();
        $artefatoTipo = ArtefatoTipo::factory()->create();

        ColetaArtefatoTipo::factory()->create([
            'coleta_id' => $coleta->id,
            'artefato_tipo_id' => $artefatoTipo->id,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        ColetaArtefatoTipo::factory()->create([
            'coleta_id' => $coleta->id,
            'artefato_tipo_id' => $artefatoTipo->id,
        ]);
    }

    public function test_relacao_coleta_retorna_artefato_tipos(): void
    {
        $coleta = Coleta::factory()->create();
        ColetaArtefatoTipo::factory()->count(2)->create(['coleta_id' => $coleta->id]);

        $this->assertCount(2, $coleta->artefatoTipos);
    }

    public function test_relacao_artefato_tipo_retorna_coleta_artefato_tipos(): void
    {
        $artefatoTipo = ArtefatoTipo::factory()->create();
        ColetaArtefatoTipo::factory()->count(3)->create(['artefato_tipo_id' => $artefatoTipo->id]);

        $this->assertCount(3, $artefatoTipo->coletaArtefatoTipos);
    }

    public function test_cascade_deleta_vinculos_ao_remover_coleta(): void
    {
        $vinculo = ColetaArtefatoTipo::factory()->create();
        $coletaId = $vinculo->coleta_id;

        $vinculo->coleta->forceDelete();

        $this->assertDatabaseMissing('coleta_artefato_tipos', ['coleta_id' => $coletaId]);
    }
}
