<?php

namespace Tests\Feature;

use App\Enums\PapelResponsavelBem;
use App\Models\BemMaterial;
use App\Models\BemResponsavel;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BemResponsavelTest extends TestCase
{
    use RefreshDatabase;

    public function test_pode_vincular_usuario_a_bem_material(): void
    {
        $responsavel = BemResponsavel::factory()->create();

        $this->assertDatabaseHas('bem_responsaveis', [
            'id' => $responsavel->id,
            'bem_material_id' => $responsavel->bem_material_id,
            'user_id' => $responsavel->user_id,
        ]);
    }

    public function test_papel_e_castado_para_enum(): void
    {
        $responsavel = BemResponsavel::factory()->pesquisador()->create();

        $this->assertInstanceOf(PapelResponsavelBem::class, $responsavel->papel);
        $this->assertSame(PapelResponsavelBem::PESQUISADOR, $responsavel->papel);
    }

    public function test_nao_permite_mesmo_usuario_duas_vezes_no_mesmo_bem(): void
    {
        $bem = BemMaterial::factory()->create();
        $usuario = User::factory()->create();

        BemResponsavel::factory()->create([
            'bem_material_id' => $bem->id,
            'user_id' => $usuario->id,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        BemResponsavel::factory()->create([
            'bem_material_id' => $bem->id,
            'user_id' => $usuario->id,
        ]);
    }

    public function test_relacao_bem_material_retorna_responsaveis(): void
    {
        $bem = BemMaterial::factory()->create();
        BemResponsavel::factory()->count(3)->create(['bem_material_id' => $bem->id]);

        $this->assertCount(3, $bem->responsaveis);
    }

    public function test_relacao_usuario_resolvida_corretamente(): void
    {
        $usuario = User::factory()->create();
        $responsavel = BemResponsavel::factory()->create(['user_id' => $usuario->id]);

        $this->assertTrue($responsavel->usuario->is($usuario));
    }

    public function test_cascade_deleta_responsaveis_ao_remover_bem_material(): void
    {
        $responsavel = BemResponsavel::factory()->create();
        $bemId = $responsavel->bem_material_id;

        $responsavel->bemMaterial->forceDelete();

        $this->assertDatabaseMissing('bem_responsaveis', ['bem_material_id' => $bemId]);
    }
}
