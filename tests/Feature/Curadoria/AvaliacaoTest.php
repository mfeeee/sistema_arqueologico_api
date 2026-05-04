<?php

namespace Tests\Feature\Curadoria;

use App\Enums\AcaoResultanteCuradoria;
use App\Enums\PerfilUsuario;
use App\Enums\StatusCuradoria;
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
            'coleta_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $response = $this->actingAs($this->curador)
            ->patchJson("/api/curadorias/{$curadoria->id}/avaliar", [
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
            'coleta_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $this->actingAs($this->coletor)
            ->patchJson("/api/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::CRIAR_SITIO->value,
            ])
            ->assertStatus(403);
    }

    public function test_curador_pode_rejeitar_coleta(): void
    {
        $coleta = Coleta::factory()->create(['usuario_id' => $this->coletor->id]);
        $curadoria = Curadoria::factory()->create([
            'coleta_id' => $coleta->id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $this->actingAs($this->curador)
            ->patchJson("/api/curadorias/{$curadoria->id}/avaliar", [
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
