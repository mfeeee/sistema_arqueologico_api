<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\RecuperacaoSenhaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_solicitacao_com_email_valido_retorna_mensagem_generica(): void
    {
        Notification::fake();

        $user = User::factory()->create(['ativo' => true]);

        $this->withHeader('Accept-Language', 'pt-BR')
            ->postJson('/api/auth/password-reset', ['email' => $user->email])
            ->assertStatus(200)
            ->assertJson(['message' => 'Se este e-mail estiver cadastrado, você receberá as instruções em breve.']);

        Notification::assertSentTo($user, RecuperacaoSenhaNotification::class);
    }

    public function test_solicitacao_com_email_inexistente_retorna_mesma_mensagem_generica(): void
    {
        Notification::fake();

        $this->withHeader('Accept-Language', 'pt-BR')
            ->postJson('/api/auth/password-reset', ['email' => 'naoexiste@teste.com'])
            ->assertStatus(200)
            ->assertJson(['message' => 'Se este e-mail estiver cadastrado, você receberá as instruções em breve.']);

        Notification::assertNothingSent();
    }

    public function test_solicitacao_com_email_invalido_falha_com_422(): void
    {
        $this->postJson('/api/auth/password-reset', ['email' => 'nao-e-um-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_confirmacao_com_token_valido_redefine_senha(): void
    {
        $user = User::factory()->create(['ativo' => true]);
        $token = Password::createToken($user);

        $this->withHeader('Accept-Language', 'pt-BR')
            ->postJson('/api/auth/password-reset/confirm', [
                'email' => $user->email,
                'token' => $token,
                'password' => 'novaSenha123',
                'password_confirmation' => 'novaSenha123',
            ])->assertStatus(200)
            ->assertJson(['message' => 'Senha redefinida com sucesso.']);

        $user->refresh();
        $this->assertTrue(Hash::check('novaSenha123', $user->password));
    }

    public function test_confirmacao_invalida_todos_os_tokens_do_usuario_apos_reset(): void
    {
        $user = User::factory()->create(['ativo' => true]);
        $sanctumToken = $user->createToken('mobile')->plainTextToken;
        $resetToken = Password::createToken($user);

        $this->postJson('/api/auth/password-reset/confirm', [
            'email' => $user->email,
            'token' => $resetToken,
            'password' => 'novaSenha456',
            'password_confirmation' => 'novaSenha456',
        ])->assertStatus(200);

        $this->withToken($sanctumToken)
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_confirmacao_com_token_invalido_retorna_422(): void
    {
        $user = User::factory()->create(['ativo' => true]);

        $this->withHeader('Accept-Language', 'pt-BR')
            ->postJson('/api/auth/password-reset/confirm', [
                'email' => $user->email,
                'token' => 'token-invalido',
                'password' => 'novaSenha123',
                'password_confirmation' => 'novaSenha123',
            ])->assertStatus(422)
            ->assertJson(['message' => 'Token inválido ou expirado.']);
    }

    public function test_confirmacao_com_token_expirado_retorna_422(): void
    {
        $user = User::factory()->create(['ativo' => true]);
        $token = Password::createToken($user);

        // Simula expiração forçando created_at para 2 horas atrás.
        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subHours(2)]);

        $this->withHeader('Accept-Language', 'pt-BR')
            ->postJson('/api/auth/password-reset/confirm', [
                'email' => $user->email,
                'token' => $token,
                'password' => 'novaSenha123',
                'password_confirmation' => 'novaSenha123',
            ])->assertStatus(422)
            ->assertJson(['message' => 'Token inválido ou expirado.']);
    }

    public function test_confirmacao_sem_campos_obrigatorios_retorna_422(): void
    {
        $this->postJson('/api/auth/password-reset/confirm', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'token', 'password']);
    }

    public function test_notificacao_usa_canal_mail_e_gera_deep_link_arqueopi(): void
    {
        $user = User::factory()->create(['ativo' => true]);
        $token = 'test-token-123';

        $notification = new RecuperacaoSenhaNotification($token);

        $this->assertSame(['mail'], $notification->via($user));

        $mail = $notification->toMail($user);
        $actionUrl = $mail->actionUrl;

        $this->assertStringStartsWith('arqueopi://reset-password', $actionUrl);
        $this->assertStringContainsString('token='.$token, $actionUrl);
        $this->assertStringContainsString('email='.urlencode($user->email), $actionUrl);
    }
}
