<?php

namespace Tests\Feature\Admin;

use App\Enums\PapelResponsavelBem;
use App\Enums\PerfilUsuario;
use App\Models\BemMaterial;
use App\Models\BemResponsavel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BemResponsavelControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $curador;

    private User $coletor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::ADMIN]);
        $this->curador = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::CURADOR]);
        $this->coletor = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::COLETOR]);
    }

    // ── autorização ───────────────────────────────────────────────────────────

    public function test_nao_autenticado_nao_pode_vincular_responsavel(): void
    {
        $bem = BemMaterial::factory()->create();
        $usuario = User::factory()->create(['ativo' => true]);

        $this->postJson("/api/v1/admin/bens-materiais/{$bem->id}/responsaveis", [
            'user_id' => $usuario->id,
            'papel' => PapelResponsavelBem::PESQUISADOR->value,
        ])->assertUnauthorized();
    }

    public function test_coletor_nao_pode_vincular_responsavel(): void
    {
        $bem = BemMaterial::factory()->create();
        $usuario = User::factory()->create(['ativo' => true]);

        $this->actingAs($this->coletor)
            ->postJson("/api/v1/admin/bens-materiais/{$bem->id}/responsaveis", [
                'user_id' => $usuario->id,
                'papel' => PapelResponsavelBem::PESQUISADOR->value,
            ])->assertForbidden();
    }

    // ── store ─────────────────────────────────────────────────────────────────

    public function test_curador_pode_vincular_responsavel_a_bem(): void
    {
        $bem = BemMaterial::factory()->create();
        $usuario = User::factory()->create(['ativo' => true]);

        $this->actingAs($this->curador)
            ->postJson("/api/v1/admin/bens-materiais/{$bem->id}/responsaveis", [
                'user_id' => $usuario->id,
                'papel' => PapelResponsavelBem::PESQUISADOR->value,
            ])->assertOk();

        $this->assertDatabaseHas('bem_responsaveis', [
            'bem_material_id' => $bem->id,
            'user_id' => $usuario->id,
            'papel' => PapelResponsavelBem::PESQUISADOR->value,
        ]);
    }

    public function test_store_rejeita_user_inativo(): void
    {
        $bem = BemMaterial::factory()->create();
        $inativo = User::factory()->create(['ativo' => false]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/bens-materiais/{$bem->id}/responsaveis", [
                'user_id' => $inativo->id,
                'papel' => PapelResponsavelBem::PESQUISADOR->value,
            ])->assertUnprocessable();
    }

    public function test_store_rejeita_user_soft_deletado(): void
    {
        $bem = BemMaterial::factory()->create();
        $deletado = User::factory()->create(['ativo' => true]);
        $deletado->delete();

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/bens-materiais/{$bem->id}/responsaveis", [
                'user_id' => $deletado->id,
                'papel' => PapelResponsavelBem::PESQUISADOR->value,
            ])->assertUnprocessable();
    }

    public function test_store_atualiza_papel_de_responsavel_existente(): void
    {
        $bem = BemMaterial::factory()->create();
        $usuario = User::factory()->create(['ativo' => true]);

        BemResponsavel::factory()->create([
            'bem_material_id' => $bem->id,
            'user_id' => $usuario->id,
            'papel' => PapelResponsavelBem::PESQUISADOR,
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/bens-materiais/{$bem->id}/responsaveis", [
                'user_id' => $usuario->id,
                'papel' => PapelResponsavelBem::COORDENADOR->value,
            ])->assertOk();

        $this->assertDatabaseHas('bem_responsaveis', [
            'bem_material_id' => $bem->id,
            'user_id' => $usuario->id,
            'papel' => PapelResponsavelBem::COORDENADOR->value,
        ]);
        $this->assertDatabaseCount('bem_responsaveis', 1);
    }

    public function test_store_rejeita_papel_invalido(): void
    {
        $bem = BemMaterial::factory()->create();
        $usuario = User::factory()->create(['ativo' => true]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/bens-materiais/{$bem->id}/responsaveis", [
                'user_id' => $usuario->id,
                'papel' => 'papel_invalido',
            ])->assertUnprocessable();
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function test_curador_pode_remover_vinculo_de_responsavel(): void
    {
        $bem = BemMaterial::factory()->create();
        $responsavel = BemResponsavel::factory()->create(['bem_material_id' => $bem->id]);

        $this->actingAs($this->curador)
            ->deleteJson("/api/v1/admin/bens-materiais/{$bem->id}/responsaveis/{$responsavel->id}")
            ->assertOk();

        $this->assertDatabaseMissing('bem_responsaveis', ['id' => $responsavel->id]);
    }

    public function test_destroy_rejeita_responsavel_de_outro_bem(): void
    {
        $bem = BemMaterial::factory()->create();
        $outroBem = BemMaterial::factory()->create();
        $responsavel = BemResponsavel::factory()->create(['bem_material_id' => $outroBem->id]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/bens-materiais/{$bem->id}/responsaveis/{$responsavel->id}")
            ->assertNotFound();
    }

    public function test_coletor_nao_pode_remover_vinculo(): void
    {
        $bem = BemMaterial::factory()->create();
        $responsavel = BemResponsavel::factory()->create(['bem_material_id' => $bem->id]);

        $this->actingAs($this->coletor)
            ->deleteJson("/api/v1/admin/bens-materiais/{$bem->id}/responsaveis/{$responsavel->id}")
            ->assertForbidden();
    }
}
