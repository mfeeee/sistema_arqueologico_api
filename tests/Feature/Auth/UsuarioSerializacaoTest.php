<?php

namespace Tests\Feature\Auth;

use App\Enums\StatusCuradoria;
use App\Models\Curadoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioSerializacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_serializado_nao_expoe_segredos(): void
    {
        $dados = User::factory()->withTwoFactor()->create(['fcm_token' => 'token-push'])->toArray();

        foreach (['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'fcm_token'] as $campo) {
            $this->assertArrayNotHasKey($campo, $dados);
        }
    }

    public function test_lista_de_curadorias_nao_traz_a_senha_do_curador(): void
    {
        $curador = User::factory()->curador()->create();
        Curadoria::factory()->create(['usuario_id' => $curador->id, 'status' => StatusCuradoria::PENDENTE]);

        $this->actingAs($curador)
            ->getJson('/api/v1/admin/curadorias')
            ->assertOk()
            ->assertJsonPath('data.0.curador.id', $curador->id)
            ->assertJsonMissingPath('data.0.curador.password');
    }
}
