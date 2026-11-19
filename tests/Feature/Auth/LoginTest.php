<?php

namespace Tests\Feature\Auth;

use App\Enums\PerfilUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_fazer_login_com_credenciais_validas(): void
    {
        $user = User::factory()->create([
            'email' => 'coletor@teste.com',
            'password' => bcrypt('senha123'),
            'ativo' => true,
            'perfil' => PerfilUsuario::COLETOR,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'coletor@teste.com',
            'password' => 'senha123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'perfil', 'classificacao'],
            ]);
    }

    public function test_login_falha_com_credenciais_invalidas(): void
    {
        User::factory()->create([
            'email' => 'coletor@teste.com',
            'password' => bcrypt('senha123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'coletor@teste.com',
            'password' => 'senha_errada',
        ])->assertStatus(401);
    }

    public function test_login_falha_para_usuario_inativo(): void
    {
        User::factory()->create([
            'email' => 'inativo@teste.com',
            'password' => bcrypt('senha123'),
            'ativo' => false,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'inativo@teste.com',
            'password' => 'senha123',
        ])->assertStatus(403);
    }

    public function test_logout_invalida_o_token(): void
    {
        $user = User::factory()->create(['ativo' => true]);
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertStatus(200);

        auth()->forgetGuards();

        // Token não deve mais funcionar
        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    // ── Plano de correções, P30 ────────────────────────────────────────────────

    public function test_11a_tentativa_no_mesmo_minuto_recebe_429(): void
    {
        $user = User::factory()->create();
        $tentativa = ['email' => $user->email, 'password' => fake()->password(12)];

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/auth/login', $tentativa)->assertUnauthorized();
        }

        $this->postJson('/api/auth/login', $tentativa)->assertTooManyRequests();
    }

    public function test_limite_de_um_email_nao_trava_outro(): void
    {
        $alvo = User::factory()->create();
        $senhaDoOutro = fake()->password(12);
        $outro = User::factory()->create(['password' => $senhaDoOutro]);

        for ($i = 0; $i < 11; $i++) {
            $this->postJson('/api/auth/login', ['email' => $alvo->email, 'password' => fake()->password(12)]);
        }

        $this->postJson('/api/auth/login', ['email' => $outro->email, 'password' => $senhaDoOutro])->assertOk();
    }
}
