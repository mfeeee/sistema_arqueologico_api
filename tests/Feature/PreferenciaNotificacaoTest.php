<?php

namespace Tests\Feature;

use App\Models\PreferenciaNotificacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferenciaNotificacaoTest extends TestCase
{
    use RefreshDatabase;

    // --- show ---

    public function test_leitura_retorna_padrao_quando_nao_existir(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/mobile/preferencias-notificacoes')
            ->assertOk()
            ->assertJson([
                'coleta' => true,
                'sync' => true,
                'sistema' => true,
                'push' => true,
            ]);

        $this->assertDatabaseHas('preferencias_notificacoes', ['user_id' => $user->id]);
    }

    public function test_leitura_retorna_preferencias_existentes(): void
    {
        $user = User::factory()->create();
        PreferenciaNotificacao::factory()->for($user)->todasDesativadas()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/mobile/preferencias-notificacoes')
            ->assertOk()
            ->assertJson([
                'coleta' => false,
                'sync' => false,
                'sistema' => false,
                'push' => false,
            ]);
    }

    public function test_leitura_requer_autenticacao(): void
    {
        $this->getJson('/api/v1/mobile/preferencias-notificacoes')
            ->assertUnauthorized();
    }

    // --- update ---

    public function test_atualizacao_persiste_preferencias(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/v1/mobile/preferencias-notificacoes', [
                'coleta' => false,
                'push' => false,
            ])
            ->assertOk()
            ->assertJson([
                'coleta' => false,
                'sync' => true,
                'sistema' => true,
                'push' => false,
            ]);

        $this->assertDatabaseHas('preferencias_notificacoes', [
            'user_id' => $user->id,
            'coleta' => false,
            'sync' => true,
            'sistema' => true,
            'push' => false,
        ]);
    }

    public function test_atualizacao_cria_registro_se_nao_existir(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseMissing('preferencias_notificacoes', ['user_id' => $user->id]);

        $this->actingAs($user)
            ->putJson('/api/v1/mobile/preferencias-notificacoes', ['sync' => false])
            ->assertOk();

        $this->assertDatabaseHas('preferencias_notificacoes', [
            'user_id' => $user->id,
            'sync' => false,
        ]);
    }

    public function test_preferencias_sao_isoladas_por_usuario(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA)
            ->putJson('/api/v1/mobile/preferencias-notificacoes', ['push' => false]);

        $this->actingAs($userB)
            ->getJson('/api/v1/mobile/preferencias-notificacoes')
            ->assertOk()
            ->assertJson(['push' => true]);

        $this->assertDatabaseHas('preferencias_notificacoes', ['user_id' => $userA->id, 'push' => false]);
        $this->assertDatabaseHas('preferencias_notificacoes', ['user_id' => $userB->id, 'push' => true]);
    }

    public function test_atualizacao_rejeita_campos_invalidos(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/v1/mobile/preferencias-notificacoes', ['coleta' => 'sim'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['coleta']);
    }

    public function test_atualizacao_rejeita_campo_desconhecido(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/v1/mobile/preferencias-notificacoes', ['urgente' => true])
            ->assertOk();

        $this->assertDatabaseMissing('preferencias_notificacoes', ['user_id' => $user->id]);
    }

    public function test_atualizacao_requer_autenticacao(): void
    {
        $this->putJson('/api/v1/mobile/preferencias-notificacoes', ['push' => false])
            ->assertUnauthorized();
    }
}
