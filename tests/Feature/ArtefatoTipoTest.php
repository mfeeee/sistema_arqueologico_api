<?php

namespace Tests\Feature;

use App\Models\ArtefatoTipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtefatoTipoTest extends TestCase
{
    use RefreshDatabase;

    public function test_pode_criar_artefato_tipo_com_campos_obrigatorios(): void
    {
        $tipo = ArtefatoTipo::factory()->create([
            'nome' => 'Lítico Lascado',
            'descricao' => 'Artefatos de pedra obtidos por lascamento.',
        ]);

        $this->assertDatabaseHas('artefato_tipos', [
            'id' => $tipo->id,
            'nome' => 'Lítico Lascado',
            'descricao' => 'Artefatos de pedra obtidos por lascamento.',
        ]);
    }

    public function test_descricao_pode_ser_nula(): void
    {
        $tipo = ArtefatoTipo::factory()->create([
            'nome' => 'Cerâmica',
            'descricao' => null,
        ]);

        $this->assertDatabaseHas('artefato_tipos', [
            'id' => $tipo->id,
            'nome' => 'Cerâmica',
            'descricao' => null,
        ]);
    }

    public function test_id_e_uuid(): void
    {
        $tipo = ArtefatoTipo::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $tipo->id
        );
    }
}
