<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // PATCH /auth/me
    // -------------------------------------------------------------------------

    public function test_atualizar_nome_retorna_200_com_novo_nome(): void
    {
        $user = User::factory()->create(['name' => 'Antigo Nome']);
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/auth/me', ['name' => 'Novo Nome'])
            ->assertOk()
            ->assertJsonFragment(['name' => 'Novo Nome']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Novo Nome']);
    }

    public function test_atualizar_email_ja_em_uso_retorna_422(): void
    {
        User::factory()->create(['email' => 'outro@teste.com']);
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/auth/me', ['email' => 'outro@teste.com'])
            ->assertUnprocessable();
    }

    public function test_atualizar_senha_sem_confirmacao_retorna_422(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/auth/me', ['password' => 'NovaSenh4'])
            ->assertUnprocessable();
    }

    // -------------------------------------------------------------------------
    // POST /auth/me/avatar
    // -------------------------------------------------------------------------

    public function test_upload_avatar_jpeg_valido_retorna_200_com_url(): void
    {
        Storage::fake('s3');
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $file = UploadedFile::fake()->create('avatar.jpg', 500, 'image/jpeg');

        $response = $this->withToken($token)
            ->postJson('/api/auth/me/avatar', ['avatar' => $file])
            ->assertOk()
            ->assertJsonStructure(['avatar_url']);

        $this->assertNotNull($response->json('avatar_url'));
        $this->assertNotNull($user->fresh()->avatar_url);
    }

    public function test_upload_avatar_arquivo_nao_imagem_retorna_422(): void
    {
        Storage::fake('s3');
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $file = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

        $this->withToken($token)
            ->postJson('/api/auth/me/avatar', ['avatar' => $file])
            ->assertUnprocessable();
    }

    public function test_upload_avatar_maior_que_2mb_retorna_422(): void
    {
        Storage::fake('s3');
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $file = UploadedFile::fake()->create('grande.jpg', 3000, 'image/jpeg');

        $this->withToken($token)
            ->postJson('/api/auth/me/avatar', ['avatar' => $file])
            ->assertUnprocessable();
    }

    // -------------------------------------------------------------------------
    // DELETE /auth/me/avatar
    // -------------------------------------------------------------------------

    public function test_delete_avatar_retorna_200_e_nulifica_no_banco(): void
    {
        Storage::fake('s3');
        $user = User::factory()->create(['avatar_url' => 'https://storage.example.com/avatars/foto.jpg']);
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/auth/me/avatar')
            ->assertOk()
            ->assertJson(['avatar_url' => null]);

        $this->assertNull($user->fresh()->avatar_url);
    }

    // -------------------------------------------------------------------------
    // Autenticação obrigatória
    // -------------------------------------------------------------------------

    public function test_get_me_sem_token_retorna_401(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_patch_me_sem_token_retorna_401(): void
    {
        $this->patchJson('/api/auth/me', ['name' => 'X'])->assertUnauthorized();
    }

    public function test_post_avatar_sem_token_retorna_401(): void
    {
        $this->postJson('/api/auth/me/avatar')->assertUnauthorized();
    }

    public function test_delete_avatar_sem_token_retorna_401(): void
    {
        $this->deleteJson('/api/auth/me/avatar')->assertUnauthorized();
    }
}
