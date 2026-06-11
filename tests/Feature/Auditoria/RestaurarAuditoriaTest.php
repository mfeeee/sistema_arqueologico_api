<?php

namespace Tests\Feature\Auditoria;

use App\Enums\PerfilUsuario;
use App\Models\Auditoria;
use App\Models\BemMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RestaurarAuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $coletor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::ADMIN]);
        $this->coletor = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::COLETOR]);
    }

    // ── autorização ───────────────────────────────────────────────────────────

    public function test_coletor_nao_pode_restaurar(): void
    {
        $bem = BemMaterial::factory()->create();
        $auditoria = Auditoria::factory()->insercao()->create([
            'usuario_id' => $this->admin->id,
            'entidade_tipo' => BemMaterial::class,
            'entidade_id' => $bem->id,
        ]);

        $this->actingAs($this->coletor)
            ->postJson("/api/v1/admin/auditorias/{$auditoria->id}/restaurar")
            ->assertStatus(403);
    }

    // ── validações ────────────────────────────────────────────────────────────

    public function test_rejeita_entidade_que_nao_e_bem_material(): void
    {
        $auditoria = Auditoria::factory()->create([
            'usuario_id' => $this->admin->id,
            'entidade_tipo' => 'App\\Models\\Curadoria',
            'operacao' => 'Alteração',
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/auditorias/{$auditoria->id}/restaurar")
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($msg) => str_contains($msg, 'sítios arqueológicos'));
    }

    public function test_rejeita_operacao_exclusao(): void
    {
        $bem = BemMaterial::factory()->create();
        $auditoria = Auditoria::factory()->create([
            'usuario_id' => $this->admin->id,
            'entidade_tipo' => BemMaterial::class,
            'entidade_id' => $bem->id,
            'operacao' => 'Exclusão',
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/auditorias/{$auditoria->id}/restaurar")
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($msg) => str_contains($msg, 'inserção ou alteração'));
    }

    public function test_retorna_404_quando_bem_nao_existe(): void
    {
        $auditoria = Auditoria::factory()->insercao()->create([
            'usuario_id' => $this->admin->id,
            'entidade_tipo' => BemMaterial::class,
            'entidade_id' => '00000000-0000-0000-0000-000000000000',
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/auditorias/{$auditoria->id}/restaurar")
            ->assertStatus(404);
    }

    // ── cenário: reverter inserção (soft delete) ──────────────────────────────

    public function test_restaurar_insercao_faz_soft_delete_do_bem(): void
    {
        $bem = BemMaterial::factory()->create();
        $auditoria = Auditoria::factory()->insercao()->create([
            'usuario_id' => $this->admin->id,
            'entidade_tipo' => BemMaterial::class,
            'entidade_id' => $bem->id,
            'valor_anterior' => null,
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/auditorias/{$auditoria->id}/restaurar")
            ->assertStatus(200)
            ->assertJsonPath('message', fn ($msg) => str_contains($msg, 'sucesso'));

        $this->assertSoftDeleted('bens_materiais', ['id' => $bem->id]);
    }

    // ── cenário: reverter alteração (restaurar campos anteriores) ─────────────

    public function test_restaurar_alteracao_restaura_campos_do_bem(): void
    {
        $bem = BemMaterial::factory()->create(['nome_bem' => 'Nome Atual']);
        $auditoria = Auditoria::factory()->alteracaoSimples(
            ['nome_bem' => 'Nome Antigo'],
            ['nome_bem' => 'Nome Atual']
        )->create([
            'usuario_id' => $this->admin->id,
            'entidade_tipo' => BemMaterial::class,
            'entidade_id' => $bem->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/auditorias/{$auditoria->id}/restaurar")
            ->assertStatus(200);

        $this->assertDatabaseHas('bens_materiais', [
            'id' => $bem->id,
            'nome_bem' => 'Nome Antigo',
        ]);
    }

    public function test_restaurar_alteracao_com_coordenadas_atualiza_geom(): void
    {
        $bem = BemMaterial::factory()->create([
            'latitude' => -8.0,
            'longitude' => -37.0,
        ]);

        $auditoria = Auditoria::factory()->alteracaoSimples(
            ['latitude' => -9.5, 'longitude' => -38.2],
            ['latitude' => -8.0, 'longitude' => -37.0]
        )->create([
            'usuario_id' => $this->admin->id,
            'entidade_tipo' => BemMaterial::class,
            'entidade_id' => $bem->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/auditorias/{$auditoria->id}/restaurar")
            ->assertStatus(200);

        $bem->refresh();
        $this->assertEquals(-9.5, $bem->latitude);
        $this->assertEquals(-38.2, $bem->longitude);

        $geom = DB::selectOne(
            'SELECT ST_X(geom::geometry) AS lng, ST_Y(geom::geometry) AS lat FROM bens_materiais WHERE id = ?',
            [$bem->id]
        );
        $this->assertEqualsWithDelta(-38.2, $geom->lng, 0.001);
        $this->assertEqualsWithDelta(-9.5, $geom->lat, 0.001);
    }

    public function test_restaurar_bem_com_soft_delete_restaura_e_reverte(): void
    {
        $bem = BemMaterial::factory()->create(['nome_bem' => 'Nome Original']);
        $bem->delete();

        $auditoria = Auditoria::factory()->alteracaoSimples(
            ['nome_bem' => 'Nome Original'],
            ['nome_bem' => 'Nome Alterado']
        )->create([
            'usuario_id' => $this->admin->id,
            'entidade_tipo' => BemMaterial::class,
            'entidade_id' => $bem->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/auditorias/{$auditoria->id}/restaurar")
            ->assertStatus(200);

        $this->assertDatabaseHas('bens_materiais', [
            'id' => $bem->id,
            'nome_bem' => 'Nome Original',
        ]);
    }
}
