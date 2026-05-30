<?php

namespace Tests\Feature\Notificacao;

use App\Enums\PerfilUsuario;
use App\Enums\TipoNotificacao;
use App\Models\Notificacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificacaoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create([
            'ativo' => true,
            'perfil' => PerfilUsuario::COLETOR,
        ]);
    }

    public function test_usuario_autenticado_pode_listar_notificacoes(): void
    {
        Notificacao::factory()->count(3)->create(['usuario_id' => $this->usuario->id]);

        $this->actingAs($this->usuario)
            ->getJson('/api/v1/mobile/notificacoes')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'titulo', 'corpo', 'tipo', 'lida', 'data'],
                ],
            ]);
    }

    public function test_listagem_retorna_apenas_notificacoes_do_usuario_autenticado(): void
    {
        $outro = User::factory()->create(['ativo' => true]);

        Notificacao::factory()->count(2)->create(['usuario_id' => $this->usuario->id]);
        Notificacao::factory()->count(5)->create(['usuario_id' => $outro->id]);

        $this->actingAs($this->usuario)
            ->getJson('/api/v1/mobile/notificacoes')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_filtra_notificacoes_por_tipo(): void
    {
        Notificacao::factory()->tipo(TipoNotificacao::Coleta)->count(2)->create(['usuario_id' => $this->usuario->id]);
        Notificacao::factory()->tipo(TipoNotificacao::Sync)->count(3)->create(['usuario_id' => $this->usuario->id]);
        Notificacao::factory()->tipo(TipoNotificacao::Sistema)->count(1)->create(['usuario_id' => $this->usuario->id]);

        $this->actingAs($this->usuario)
            ->getJson('/api/v1/mobile/notificacoes?tipo=coleta')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $this->actingAs($this->usuario)
            ->getJson('/api/v1/mobile/notificacoes?tipo=sync')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_listagem_sem_filtro_retorna_todos_os_tipos(): void
    {
        Notificacao::factory()->tipo(TipoNotificacao::Coleta)->create(['usuario_id' => $this->usuario->id]);
        Notificacao::factory()->tipo(TipoNotificacao::Sync)->create(['usuario_id' => $this->usuario->id]);
        Notificacao::factory()->tipo(TipoNotificacao::Sistema)->create(['usuario_id' => $this->usuario->id]);

        $this->actingAs($this->usuario)
            ->getJson('/api/v1/mobile/notificacoes')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_filtro_com_tipo_invalido_retorna_erro_de_validacao(): void
    {
        $this->actingAs($this->usuario)
            ->getJson('/api/v1/mobile/notificacoes?tipo=invalido')
            ->assertStatus(422);
    }

    public function test_usuario_pode_marcar_notificacao_como_lida(): void
    {
        $notificacao = Notificacao::factory()->create(['usuario_id' => $this->usuario->id]);

        $response = $this->actingAs($this->usuario)
            ->patchJson("/api/v1/mobile/notificacoes/{$notificacao->id}/lida");

        $response->assertStatus(200)
            ->assertJsonFragment(['lida' => true]);

        $this->assertDatabaseHas('notificacoes', [
            'id' => $notificacao->id,
            'lida' => true,
        ]);
    }

    public function test_marcar_como_lida_nao_atualiza_lida_em_se_ja_estava_lida(): void
    {
        $lidaEm = now()->subDay();
        $notificacao = Notificacao::factory()->lida()->create([
            'usuario_id' => $this->usuario->id,
            'lida_em' => $lidaEm,
        ]);

        $this->actingAs($this->usuario)
            ->patchJson("/api/v1/mobile/notificacoes/{$notificacao->id}/lida")
            ->assertStatus(200);

        $this->assertDatabaseHas('notificacoes', [
            'id' => $notificacao->id,
            'lida' => true,
        ]);

        $notificacao->refresh();
        $this->assertTrue($notificacao->lida_em->equalTo($lidaEm->startOfSecond()));
    }

    public function test_usuario_nao_pode_marcar_notificacao_de_outro_usuario_como_lida(): void
    {
        $outro = User::factory()->create(['ativo' => true]);
        $notificacao = Notificacao::factory()->create(['usuario_id' => $outro->id]);

        $this->actingAs($this->usuario)
            ->patchJson("/api/v1/mobile/notificacoes/{$notificacao->id}/lida")
            ->assertStatus(403);
    }

    public function test_acesso_sem_autenticacao_e_negado_na_listagem(): void
    {
        $this->getJson('/api/v1/mobile/notificacoes')
            ->assertStatus(401);
    }

    public function test_acesso_sem_autenticacao_e_negado_ao_marcar_como_lida(): void
    {
        $notificacao = Notificacao::factory()->create(['usuario_id' => $this->usuario->id]);

        $this->patchJson("/api/v1/mobile/notificacoes/{$notificacao->id}/lida")
            ->assertStatus(401);
    }
}
