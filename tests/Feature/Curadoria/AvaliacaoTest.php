<?php

namespace Tests\Feature\Curadoria;

use App\Enums\AcaoResultanteCuradoria;
use App\Enums\PerfilUsuario;
use App\Enums\StatusCuradoria;
use App\Models\BemMaterial;
use App\Models\Coleta;
use App\Models\Curadoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvaliacaoTest extends TestCase
{
    use RefreshDatabase;

    private User $curador;

    private User $coletor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->curador = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::CURADOR]);
        $this->coletor = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::COLETOR]);
    }

    public function test_curador_pode_aprovar_e_criar_sitio(): void
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);
        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $response = $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::CRIAR_SITIO->value,
                'observacao' => 'Sítio validado em campo.',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('curadorias', [
            'id' => $curadoria->id,
            'status' => StatusCuradoria::APROVADO->value,
        ]);

        $this->assertDatabaseHas('bens_materiais', [
            'coleta_id' => $coleta->id,
        ]);

        $this->assertDatabaseHas('coletas', [
            'id' => $coleta->id,
            'status_sincronizacao' => 'sincronizado',
        ]);
    }

    public function test_coletor_nao_pode_avaliar_curadoria(): void
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);
        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $this->actingAs($this->coletor)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::CRIAR_SITIO->value,
            ])
            ->assertStatus(403);
    }

    // ── atualizarSitio ─────────────────────────────────────────────────────────

    public function test_curador_pode_aprovar_e_atualizar_sitio(): void
    {
        $bem = BemMaterial::factory()->create(['nome_bem' => 'Nome Original']);
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);

        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
            'bem_material_id' => null,
        ]);

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::ATUALIZAR_SITIO->value,
                'bem_material_id' => $bem->id,
                'campos' => ['nome_bem' => 'Nome Atualizado pelo Curador'],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('curadorias', [
            'id' => $curadoria->id,
            'status' => StatusCuradoria::APROVADO->value,
            'acao_resultante' => AcaoResultanteCuradoria::ATUALIZAR_SITIO->value,
        ]);

        $this->assertDatabaseHas('bens_materiais', [
            'id' => $bem->id,
            'nome_bem' => 'Nome Atualizado pelo Curador',
        ]);
    }

    public function test_atualizar_sitio_requer_bem_material_id(): void
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);
        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::ATUALIZAR_SITIO->value,
                // sem bem_material_id
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['bem_material_id']);
    }

    // ── auditoria integration ──────────────────────────────────────────────────

    public function test_aprovacao_criar_sitio_gera_auditoria_insercao(): void
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);
        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::CRIAR_SITIO->value,
            ])
            ->assertStatus(200);

        $bem = BemMaterial::where('coleta_id', $coleta->id)->firstOrFail();

        $this->assertDatabaseHas('auditorias', [
            'entidade_tipo' => BemMaterial::class,
            'entidade_id' => $bem->id,
            'curadoria_id' => $curadoria->id,
            'operacao' => 'Inserção',
            'meio' => 'Curadoria',
        ]);
    }

    public function test_aprovacao_atualizar_sitio_gera_auditoria_alteracao(): void
    {
        $bem = BemMaterial::factory()->create(['nome_bem' => 'Nome Original']);
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);

        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
            'bem_material_id' => null,
        ]);

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::ATUALIZAR_SITIO->value,
                'bem_material_id' => $bem->id,
                'campos' => ['descricao_atualizacao' => 'Descrição revisada em campo.'],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('auditorias', [
            'entidade_tipo' => BemMaterial::class,
            'entidade_id' => $bem->id,
            'curadoria_id' => $curadoria->id,
            'operacao' => 'Alteração',
            'meio' => 'Curadoria',
        ]);
    }

    public function test_rejeicao_gera_auditoria_rejeicao(): void
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);
        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::REJEITADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::REJEITAR->value,
                'observacao' => 'Localização imprecisa.',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('auditorias', [
            'entidade_tipo' => Curadoria::class,
            'entidade_id' => $curadoria->id,
            'curadoria_id' => $curadoria->id,
            'operacao' => 'Rejeição',
            'meio' => 'Curadoria',
        ]);
    }

    // ── listing / show ─────────────────────────────────────────────────────────

    public function test_curador_pode_listar_curadorias_pendentes(): void
    {
        Curadoria::factory()->count(3)->create([
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $this->actingAs($this->curador)
            ->getJson('/api/v1/admin/curadorias')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_curador_pode_ver_curadoria_individual(): void
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);
        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
        ]);

        $this->actingAs($this->curador)
            ->getJson("/api/v1/admin/curadorias/{$curadoria->id}")
            ->assertStatus(200)
            ->assertJsonPath('id', $curadoria->id)
            ->assertJsonPath('entidade_tipo', 'coleta');
    }

    public function test_coletor_nao_pode_listar_curadorias(): void
    {
        $this->actingAs($this->coletor)
            ->getJson('/api/v1/admin/curadorias')
            ->assertStatus(403);
    }

    public function test_curador_pode_rejeitar_coleta(): void
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);
        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'coleta',
            'entidade_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::REJEITADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::REJEITAR->value,
                'observacao' => 'Dados insuficientes.',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('curadorias', [
            'id' => $curadoria->id,
            'status' => StatusCuradoria::REJEITADO->value,
        ]);

        // Nenhum bem material deve ter sido criado
        $this->assertDatabaseMissing('bens_materiais', [
            'coleta_id' => $coleta->id,
        ]);
    }
}
