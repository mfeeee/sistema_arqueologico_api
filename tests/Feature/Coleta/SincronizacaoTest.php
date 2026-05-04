<?php

namespace Tests\Feature\Coleta;

use App\Enums\NaturezaBem;
use App\Enums\PerfilUsuario;
use App\Jobs\ProcessarSincronizacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SincronizacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_enfileira_job_e_retorna_202(): void
    {
        Queue::fake();

        $user = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::COLETOR]);

        $payload = [
            'coletas' => [
                [
                    'id' => fake()->uuid(),
                    'data_coleta' => '2026-05-01 08:00:00',
                    'nome_bem' => 'Sítio Offline A',
                    'latitude' => -5.09,
                    'longitude' => -42.80,
                    'natureza' => NaturezaBem::ARQUEOLOGICO->value,
                    'versao' => 1,
                ],
            ],
        ];

        $this->actingAs($user)
            ->postJson('/api/sync', $payload)
            ->assertStatus(202)
            ->assertJsonFragment(['total_itens' => 1]);

        Queue::assertPushed(ProcessarSincronizacao::class);
    }

    public function test_sync_falha_sem_autenticacao(): void
    {
        $this->postJson('/api/sync', ['coletas' => []])
            ->assertStatus(401);
    }

    public function test_sync_rejeita_payload_invalido(): void
    {
        $user = User::factory()->create(['ativo' => true]);

        // latitude fora do range
        $this->actingAs($user)
            ->postJson('/api/sync', [
                'coletas' => [[
                    'id' => fake()->uuid(),
                    'data_coleta' => '2026-05-01',
                    'nome_bem' => 'Sítio',
                    'latitude' => 999,
                    'longitude' => -42.80,
                    'versao' => 1,
                ]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['coletas.0.latitude']);
    }
}
