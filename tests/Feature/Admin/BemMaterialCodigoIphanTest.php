<?php

namespace Tests\Feature\Admin;

use App\Models\Auditoria;
use App\Models\BemMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Plano de correções, P20: o curador preenche o número do CNSA depois da aprovação.
 */
class BemMaterialCodigoIphanTest extends TestCase
{
    use RefreshDatabase;

    public function test_curador_grava_o_codigo_e_fica_auditado(): void
    {
        $curador = User::factory()->curador()->create();
        $bem = BemMaterial::factory()->create(['codigo_iphan' => null]);

        $this->actingAs($curador)
            ->patchJson("/api/v1/admin/bens-materiais/{$bem->id}/codigo-iphan", ['codigo_iphan' => 'PI-00123'])
            ->assertOk()
            ->assertJsonPath('codigo_iphan', 'PI-00123');

        $auditoria = Auditoria::where('entidade_id', $bem->id)->sole();
        $this->assertSame($curador->id, $auditoria->usuario_id);
        $this->assertSame(['codigo_iphan' => null], $auditoria->valor_anterior);
        $this->assertSame(['codigo_iphan' => 'PI-00123'], $auditoria->valor_novo);
    }

    public function test_coletor_nao_pode(): void
    {
        $bem = BemMaterial::factory()->create(['codigo_iphan' => null]);

        $this->actingAs(User::factory()->create())
            ->patchJson("/api/v1/admin/bens-materiais/{$bem->id}/codigo-iphan", ['codigo_iphan' => 'PI-00123'])
            ->assertForbidden();

        $this->assertNull($bem->fresh()->codigo_iphan);
    }

    public function test_codigo_de_outro_sitio_e_recusado(): void
    {
        BemMaterial::factory()->create(['codigo_iphan' => 'PI-00123']);
        $bem = BemMaterial::factory()->create(['codigo_iphan' => null]);

        $this->actingAs(User::factory()->curador()->create())
            ->patchJson("/api/v1/admin/bens-materiais/{$bem->id}/codigo-iphan", ['codigo_iphan' => 'PI-00123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('codigo_iphan');
    }

    public function test_campo_vazio_apaga_o_codigo(): void
    {
        $bem = BemMaterial::factory()->create(['codigo_iphan' => 'PI-00123']);

        $this->actingAs(User::factory()->curador()->create())
            ->patchJson("/api/v1/admin/bens-materiais/{$bem->id}/codigo-iphan", ['codigo_iphan' => ''])
            ->assertOk();

        $this->assertNull($bem->fresh()->codigo_iphan);
    }
}
