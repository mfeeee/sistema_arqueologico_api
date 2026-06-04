<?php

namespace Tests\Feature;

use App\Enums\TipoMidia;
use App\Models\BemMaterial;
use App\Models\Coleta;
use App\Models\Midia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pode_criar_midia_para_bem_material(): void
    {
        $midia = Midia::factory()->paraBemMaterial()->imagem()->create();

        $this->assertDatabaseHas('midias', [
            'id' => $midia->id,
            'mediable_type' => BemMaterial::class,
            'tipo' => TipoMidia::IMAGEM->value,
        ]);
    }

    public function test_pode_criar_midia_para_coleta(): void
    {
        $midia = Midia::factory()->paraColeta()->create(['tipo' => TipoMidia::VIDEO]);

        $this->assertDatabaseHas('midias', [
            'id' => $midia->id,
            'mediable_type' => Coleta::class,
            'tipo' => TipoMidia::VIDEO->value,
        ]);
    }

    public function test_bem_material_retorna_midias_polimorficas(): void
    {
        $bem = BemMaterial::factory()->create();
        Midia::factory()->count(3)->create([
            'mediable_type' => BemMaterial::class,
            'mediable_id' => $bem->id,
        ]);

        $this->assertCount(3, $bem->midias);
    }

    public function test_coleta_retorna_midias_polimorficas(): void
    {
        $coleta = Coleta::factory()->create();
        Midia::factory()->count(2)->create([
            'mediable_type' => Coleta::class,
            'mediable_id' => $coleta->id,
        ]);

        $this->assertCount(2, $coleta->midias);
    }

    public function test_mediable_resolve_modelo_correto(): void
    {
        $bem = BemMaterial::factory()->create();
        $midia = Midia::factory()->create([
            'mediable_type' => BemMaterial::class,
            'mediable_id' => $bem->id,
        ]);

        $this->assertInstanceOf(BemMaterial::class, $midia->mediable);
        $this->assertTrue($midia->mediable->is($bem));
    }

    public function test_descricao_pode_ser_nula(): void
    {
        $midia = Midia::factory()->create(['descricao' => null]);

        $this->assertNull($midia->descricao);
    }
}
