<?php

namespace Tests\Feature\Auth;

use App\Enums\ClassificacaoUsuario;
use App\Enums\PerfilUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_cadastro_valido_cria_coletor(): void
    {
        $this->postJson('/api/auth/register', $this->dadosValidos())
            ->assertCreated()
            ->assertJsonPath('user.perfil', PerfilUsuario::COLETOR->value)
            ->assertJsonStructure(['token']);
    }

    public function test_cadastro_rejeita_dados_invalidos(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'nao-e-email',
            'password' => '1',
            'password_confirmation' => '2',
            'classificacao' => 'xyz',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password', 'classificacao']);
    }

    public function test_cadastro_com_email_repetido_responde_422(): void
    {
        User::factory()->create(['email' => 'ana@exemplo.com']);

        $this->postJson('/api/auth/register', $this->dadosValidos())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_cadastro_ignora_perfil_enviado_pelo_cliente(): void
    {
        $this->postJson('/api/auth/register', $this->dadosValidos(['perfil' => PerfilUsuario::ADMIN->value]))
            ->assertCreated();

        $this->assertSame(PerfilUsuario::COLETOR, User::where('email', 'ana@exemplo.com')->firstOrFail()->perfil);
    }

    public function test_cadastro_guarda_quando_os_termos_foram_aceitos(): void
    {
        $this->postJson('/api/auth/register', $this->dadosValidos(['termos_aceitos' => true]))
            ->assertCreated();

        $this->assertNotNull(User::where('email', 'ana@exemplo.com')->firstOrFail()->termos_aceitos_em);
    }

    public function test_cadastro_com_termos_recusados_responde_422(): void
    {
        $this->postJson('/api/auth/register', $this->dadosValidos(['termos_aceitos' => false]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['termos_aceitos']);
    }

    public function test_cadastro_sem_o_campo_ainda_passa_sem_data_de_aceite(): void
    {
        $this->postJson('/api/auth/register', $this->dadosValidos())->assertCreated();

        $this->assertNull(User::where('email', 'ana@exemplo.com')->firstOrFail()->termos_aceitos_em);
    }

    /**
     * @param  array<string, mixed>  $sobrescrever
     * @return array<string, mixed>
     */
    private function dadosValidos(array $sobrescrever = []): array
    {
        return array_merge([
            'name' => 'Ana Coletora',
            'email' => 'ana@exemplo.com',
            'password' => 'Senha-forte-123',
            'password_confirmation' => 'Senha-forte-123',
            'classificacao' => ClassificacaoUsuario::ARQUEOLOGO->value,
        ], $sobrescrever);
    }
}
