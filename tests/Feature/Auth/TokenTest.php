<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Plano de correções, P29 (decisão D5): token de 30 dias, refresh e conta desativada.
 */
class TokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_com_mais_de_30_dias_nao_vale(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile', [$user->perfil->value]);
        $token->accessToken->forceFill(['created_at' => now()->subDays(31)])->save();

        $this->withToken($token->plainTextToken)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_refresh_entrega_token_novo_e_o_antigo_para_de_valer(): void
    {
        $user = User::factory()->create();
        $antigo = $user->createToken('mobile', [$user->perfil->value])->plainTextToken;

        $novo = $this->withToken($antigo)->postJson('/api/auth/refresh')->assertOk()->json('token');
        $this->assertIsString($novo);

        // O guard guarda o usuário entre as requisições de um mesmo teste.
        Auth::forgetGuards();
        $this->withToken($antigo)->getJson('/api/auth/me')->assertUnauthorized();

        Auth::forgetGuards();
        $this->withToken($novo)->getJson('/api/auth/me')->assertOk();
    }

    public function test_conta_desativada_perde_o_acesso_com_o_token_que_ja_tinha(): void
    {
        $user = User::factory()->create(['ativo' => false]);
        $token = $user->createToken('mobile', [$user->perfil->value])->plainTextToken;

        $this->withToken($token)->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', __('errors.account_disabled'));

        Auth::forgetGuards();
        $this->withToken($token)->postJson('/api/auth/refresh')->assertUnauthorized();

        // Nem nas rotas públicas o token vale (curador desativado veria e-mails de colaboradores).
        Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/v1/mobile/bens-materiais')->assertUnauthorized();
    }
}
