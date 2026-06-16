<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnonimizarContaTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_conta_anonimiza_dados_pessoais_e_soft_deleta(): void
    {
        $user = User::factory()->create(['name' => 'João Silva', 'email' => 'joao@email.com']);
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/auth/conta')
            ->assertOk()
            ->assertJsonFragment(['message' => 'Conta excluída com sucesso.']);

        $fresh = User::withTrashed()->find($user->id);
        $this->assertNotNull($fresh->deleted_at);
        $this->assertEquals('Usuário excluído', $fresh->name);
        $this->assertStringEndsWith('@excluido.local', $fresh->email);
        $this->assertFalse($fresh->ativo);
        $this->assertNull($fresh->avatar_url);
        $this->assertNull($fresh->remember_token);
    }

    public function test_delete_conta_revoga_todos_os_tokens_sanctum(): void
    {
        $user = User::factory()->create();
        $user->createToken('mobile');
        $user->createToken('web');
        $token = $user->createToken('current')->plainTextToken;

        $this->withToken($token)->deleteJson('/api/auth/conta')->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_delete_conta_cria_registro_auditoria(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)->deleteJson('/api/auth/conta')->assertOk();

        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $user->id,
            'entidade_id' => $user->id,
            'operacao' => 'Anonimização',
        ]);
    }

    public function test_delete_conta_sem_token_retorna_401(): void
    {
        $this->deleteJson('/api/auth/conta')->assertUnauthorized();
    }
}
