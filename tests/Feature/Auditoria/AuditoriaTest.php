<?php

namespace Tests\Feature\Auditoria;

use App\Enums\PerfilUsuario;
use App\Models\Auditoria;
use App\Models\BemMaterial;
use App\Models\Curadoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaTest extends TestCase
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

    public function test_admin_pode_listar_auditorias(): void
    {
        Auditoria::factory()->count(3)->create(['usuario_id' => $this->admin->id]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/auditorias')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_curador_pode_listar_auditorias(): void
    {
        Auditoria::factory()->count(2)->create(['usuario_id' => $this->curador->id]);

        $this->actingAs($this->curador)
            ->getJson('/api/v1/admin/auditorias')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_coletor_nao_pode_listar_auditorias(): void
    {
        $this->actingAs($this->coletor)
            ->getJson('/api/v1/admin/auditorias')
            ->assertStatus(403);
    }

    public function test_admin_pode_ver_auditoria_individual(): void
    {
        $auditoria = Auditoria::factory()->create(['usuario_id' => $this->admin->id]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/auditorias/'.$auditoria->id)
            ->assertStatus(200)
            ->assertJsonPath('id', $auditoria->id)
            ->assertJsonStructure(['usuario', 'curadoria']);
    }

    // ── filtros ───────────────────────────────────────────────────────────────

    public function test_admin_filtra_por_entidade_tipo(): void
    {
        Auditoria::factory()->count(2)->create([
            'usuario_id' => $this->admin->id,
            'entidade_tipo' => BemMaterial::class,
        ]);
        Auditoria::factory()->create([
            'usuario_id' => $this->admin->id,
            'entidade_tipo' => Curadoria::class,
        ]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/auditorias?entidade_tipo='.urlencode(BemMaterial::class))
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_filtra_por_entidade_id(): void
    {
        $bem = BemMaterial::factory()->create();

        Auditoria::factory()->count(2)->create([
            'usuario_id' => $this->admin->id,
            'entidade_tipo' => BemMaterial::class,
            'entidade_id' => $bem->id,
        ]);
        Auditoria::factory()->create([
            'usuario_id' => $this->admin->id,
            'entidade_tipo' => BemMaterial::class,
        ]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/auditorias?entidade_id='.$bem->id)
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_filtra_por_usuario_id(): void
    {
        Auditoria::factory()->count(2)->create(['usuario_id' => $this->admin->id]);
        Auditoria::factory()->count(3)->create(['usuario_id' => $this->curador->id]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/auditorias?usuario_id='.$this->curador->id)
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    // ── model / relacionamentos ───────────────────────────────────────────────

    public function test_auditoria_manual_tem_curadoria_id_nulo(): void
    {
        $auditoria = Auditoria::factory()->manual()->create(['usuario_id' => $this->admin->id]);

        $this->assertNull($auditoria->curadoria_id);
        $this->assertSame('Manual', $auditoria->meio);
    }

    public function test_auditoria_vinculada_a_curadoria_carrega_relacao(): void
    {
        $curadoria = Curadoria::factory()->create(['usuario_id' => $this->admin->id]);
        $auditoria = Auditoria::factory()
            ->insercao()
            ->paraCuradoria($curadoria)
            ->create(['usuario_id' => $this->admin->id]);

        $this->assertNotNull($auditoria->curadoria_id);
        $this->assertTrue($auditoria->curadoria->is($curadoria));
    }

    public function test_auditoria_insercao_tem_valor_anterior_nulo(): void
    {
        $bem = BemMaterial::factory()->create();
        $auditoria = Auditoria::factory()->insercao(['nome_bem' => $bem->nome_bem])->create([
            'usuario_id' => $this->admin->id,
            'entidade_id' => $bem->id,
        ]);

        $this->assertNull($auditoria->valor_anterior);
        $this->assertArrayHasKey('nome_bem', $auditoria->valor_novo);
    }

    public function test_auditoria_alteracao_tem_valor_anterior_e_novo(): void
    {
        $bem = BemMaterial::factory()->create();
        $auditoria = Auditoria::factory()->alteracaoSimples(
            ['nome_bem' => 'Nome Velho'],
            ['nome_bem' => 'Nome Novo']
        )->create([
            'usuario_id' => $this->curador->id,
            'entidade_id' => $bem->id,
        ]);

        $this->assertSame('Nome Velho', $auditoria->valor_anterior['nome_bem']);
        $this->assertSame('Nome Novo', $auditoria->valor_novo['nome_bem']);
    }

    public function test_auditoria_show_carrega_usuario_e_curadoria(): void
    {
        $curadoria = Curadoria::factory()->create(['usuario_id' => $this->curador->id]);
        $auditoria = Auditoria::factory()
            ->paraCuradoria($curadoria)
            ->create(['usuario_id' => $this->curador->id]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/auditorias/'.$auditoria->id)
            ->assertStatus(200)
            ->assertJsonPath('id', $auditoria->id)
            ->assertJsonPath('usuario.id', $this->curador->id)
            ->assertJsonPath('curadoria.id', $curadoria->id);
    }
}
