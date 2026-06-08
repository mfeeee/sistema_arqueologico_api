<?php

namespace Tests\Feature\Notificacao;

use App\Enums\PerfilUsuario;
use App\Models\PreferenciaNotificacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferenciaNotificacaoTest extends TestCase
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

    // ── GET /preferencias-notificacoes ─────────────────────────────────────────

    public function test_get_retorna_preferencias_existentes(): void
    {
        PreferenciaNotificacao::factory()->comColetaDesativada()->create([
            'user_id' => $this->usuario->id,
        ]);

        $this->actingAs($this->usuario)
            ->getJson('/api/v1/mobile/preferencias-notificacoes')
            ->assertOk()
            ->assertJson([
                'coleta' => false,
                'sync' => true,
                'sistema' => true,
                'push' => true,
            ]);
    }

    public function test_get_cria_preferencias_padrao_quando_nao_existem(): void
    {
        $this->assertDatabaseMissing('preferencias_notificacoes', ['user_id' => $this->usuario->id]);

        $this->actingAs($this->usuario)
            ->getJson('/api/v1/mobile/preferencias-notificacoes')
            ->assertOk()
            ->assertJson([
                'coleta' => true,
                'sync' => true,
                'sistema' => true,
                'push' => true,
            ]);

        $this->assertDatabaseHas('preferencias_notificacoes', ['user_id' => $this->usuario->id]);
    }

    public function test_get_retorna_estrutura_completa(): void
    {
        $this->actingAs($this->usuario)
            ->getJson('/api/v1/mobile/preferencias-notificacoes')
            ->assertOk()
            ->assertJsonStructure(['coleta', 'sync', 'sistema', 'push']);
    }

    public function test_get_sem_autenticacao_negado(): void
    {
        $this->getJson('/api/v1/mobile/preferencias-notificacoes')
            ->assertStatus(401);
    }

    // ── PUT /preferencias-notificacoes ─────────────────────────────────────────

    public function test_put_atualiza_campo_coleta(): void
    {
        PreferenciaNotificacao::factory()->create(['user_id' => $this->usuario->id]);

        $this->actingAs($this->usuario)
            ->putJson('/api/v1/mobile/preferencias-notificacoes', ['coleta' => false])
            ->assertOk()
            ->assertJson(['coleta' => false, 'sync' => true, 'sistema' => true, 'push' => true]);

        $this->assertDatabaseHas('preferencias_notificacoes', [
            'user_id' => $this->usuario->id,
            'coleta' => false,
        ]);
    }

    public function test_put_atualiza_multiplos_campos(): void
    {
        PreferenciaNotificacao::factory()->create(['user_id' => $this->usuario->id]);

        $this->actingAs($this->usuario)
            ->putJson('/api/v1/mobile/preferencias-notificacoes', [
                'coleta' => false,
                'push' => false,
            ])
            ->assertOk()
            ->assertJson(['coleta' => false, 'sync' => true, 'sistema' => true, 'push' => false]);
    }

    public function test_put_cria_preferencias_se_nao_existirem(): void
    {
        $this->assertDatabaseMissing('preferencias_notificacoes', ['user_id' => $this->usuario->id]);

        $this->actingAs($this->usuario)
            ->putJson('/api/v1/mobile/preferencias-notificacoes', ['sistema' => false])
            ->assertOk()
            ->assertJson(['sistema' => false]);

        $this->assertDatabaseHas('preferencias_notificacoes', [
            'user_id' => $this->usuario->id,
            'sistema' => false,
        ]);
    }

    public function test_put_sem_campos_retorna_valores_padrao(): void
    {
        $this->actingAs($this->usuario)
            ->putJson('/api/v1/mobile/preferencias-notificacoes', [])
            ->assertOk()
            ->assertJson(['coleta' => true, 'sync' => true, 'sistema' => true, 'push' => true]);
    }

    public function test_put_campo_invalido_retorna_erro_de_validacao(): void
    {
        $this->actingAs($this->usuario)
            ->putJson('/api/v1/mobile/preferencias-notificacoes', ['coleta' => 'nao_booleano'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['coleta']);
    }

    public function test_put_sem_autenticacao_negado(): void
    {
        $this->putJson('/api/v1/mobile/preferencias-notificacoes', ['coleta' => false])
            ->assertStatus(401);
    }

    // ── isolamento por usuário ─────────────────────────────────────────────────

    public function test_preferencias_sao_isoladas_por_usuario(): void
    {
        $outro = User::factory()->create(['ativo' => true]);

        PreferenciaNotificacao::factory()->todasDesativadas()->create(['user_id' => $outro->id]);
        PreferenciaNotificacao::factory()->create(['user_id' => $this->usuario->id]);

        $this->actingAs($this->usuario)
            ->getJson('/api/v1/mobile/preferencias-notificacoes')
            ->assertOk()
            ->assertJson(['coleta' => true, 'sync' => true, 'sistema' => true, 'push' => true]);
    }

    public function test_put_nao_afeta_preferencias_de_outro_usuario(): void
    {
        $outro = User::factory()->create(['ativo' => true]);
        PreferenciaNotificacao::factory()->create(['user_id' => $outro->id]);
        PreferenciaNotificacao::factory()->create(['user_id' => $this->usuario->id]);

        $this->actingAs($this->usuario)
            ->putJson('/api/v1/mobile/preferencias-notificacoes', ['push' => false])
            ->assertOk();

        $this->assertDatabaseHas('preferencias_notificacoes', [
            'user_id' => $outro->id,
            'push' => true,
        ]);
    }
}
