<?php

namespace Tests\Feature\Admin;

use App\Enums\PerfilUsuario;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserControllerTest extends TestCase
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

    // ── index — autorização ───────────────────────────────────────────────────

    public function test_nao_autenticado_nao_pode_listar_usuarios(): void
    {
        $this->getJson('/api/v1/admin/usuarios')->assertUnauthorized();
    }

    public function test_coletor_nao_pode_listar_usuarios(): void
    {
        $this->actingAs($this->coletor)
            ->getJson('/api/v1/admin/usuarios')
            ->assertForbidden();
    }

    public function test_curador_nao_pode_listar_usuarios(): void
    {
        $this->actingAs($this->curador)
            ->getJson('/api/v1/admin/usuarios')
            ->assertForbidden();
    }

    // ── index — comportamento ─────────────────────────────────────────────────

    public function test_admin_pode_listar_usuarios_paginado(): void
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/usuarios')
            ->assertOk();

        $response->assertJsonStructure(['data', 'total', 'per_page', 'current_page']);
    }

    public function test_filtro_q_filtra_por_nome(): void
    {
        User::factory()->create(['name' => 'Ana Lima', 'email' => 'ana@test.com']);
        User::factory()->create(['name' => 'Carlos Mendes', 'email' => 'carlos@test.com']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/usuarios?q=Ana')
            ->assertOk();

        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.name', 'Ana Lima');
    }

    public function test_filtro_q_filtra_por_email(): void
    {
        User::factory()->create(['name' => 'Fulano', 'email' => 'busca@example.com']);
        User::factory()->create(['name' => 'Outro', 'email' => 'outro@example.com']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/usuarios?q=busca@example')
            ->assertOk();

        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.email', 'busca@example.com');
    }

    public function test_filtro_perfil_filtra_por_perfil(): void
    {
        User::factory()->count(3)->create(['perfil' => PerfilUsuario::COLETOR]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/usuarios?perfil=coletor')
            ->assertOk();

        foreach ($response->json('data') as $usuario) {
            $this->assertSame('coletor', $usuario['perfil']);
        }
    }

    // ── updatePerfil — autorização ────────────────────────────────────────────

    public function test_nao_autenticado_nao_pode_alterar_perfil(): void
    {
        $usuario = User::factory()->create(['perfil' => PerfilUsuario::COLETOR]);

        $this->patchJson("/api/v1/admin/usuarios/{$usuario->id}/perfil", ['perfil' => 'curador'])
            ->assertUnauthorized();
    }

    public function test_curador_nao_pode_alterar_perfil_de_usuario(): void
    {
        $usuario = User::factory()->create(['perfil' => PerfilUsuario::COLETOR]);

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/usuarios/{$usuario->id}/perfil", ['perfil' => 'curador'])
            ->assertForbidden();
    }

    // ── updatePerfil — regras de negócio ──────────────────────────────────────

    public function test_admin_pode_alterar_perfil_de_usuario(): void
    {
        $usuario = User::factory()->create(['perfil' => PerfilUsuario::COLETOR]);

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/usuarios/{$usuario->id}/perfil", ['perfil' => 'curador'])
            ->assertOk()
            ->assertJsonPath('perfil', 'curador');

        $this->assertSame(PerfilUsuario::CURADOR, $usuario->fresh()->perfil);
    }

    public function test_admin_nao_pode_alterar_o_proprio_perfil(): void
    {
        $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/usuarios/{$this->admin->id}/perfil", ['perfil' => 'coletor'])
            ->assertForbidden();
    }

    public function test_nao_pode_alterar_perfil_de_outro_admin(): void
    {
        $outroAdmin = User::factory()->create(['perfil' => PerfilUsuario::ADMIN]);

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/usuarios/{$outroAdmin->id}/perfil", ['perfil' => 'coletor'])
            ->assertForbidden();
    }

    public function test_perfil_invalido_retorna_422(): void
    {
        $usuario = User::factory()->create(['perfil' => PerfilUsuario::COLETOR]);

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/usuarios/{$usuario->id}/perfil", ['perfil' => 'superusuario'])
            ->assertUnprocessable();
    }

    public function test_alterar_perfil_cria_registro_de_auditoria(): void
    {
        $usuario = User::factory()->create(['perfil' => PerfilUsuario::COLETOR]);

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/usuarios/{$usuario->id}/perfil", ['perfil' => 'curador'])
            ->assertOk();

        $auditoria = Auditoria::where('entidade_tipo', User::class)
            ->where('entidade_id', $usuario->id)
            ->where('operacao', 'Alteração')
            ->firstOrFail();

        $this->assertSame('coletor', $auditoria->valor_anterior['perfil']);
        $this->assertSame('curador', $auditoria->valor_novo['perfil']);
        $this->assertSame($this->admin->id, $auditoria->usuario_id);
    }
}
