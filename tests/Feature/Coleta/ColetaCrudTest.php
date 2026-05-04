<?php

namespace Tests\Feature\Coleta;

use App\Enums\NaturezaBem;
use App\Enums\PerfilUsuario;
use App\Models\Coleta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColetaCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $coletor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coletor = User::factory()->create([
            'ativo' => true,
            'perfil' => PerfilUsuario::COLETOR,
        ]);
    }

    public function test_coletor_pode_criar_coleta(): void
    {
        $response = $this->actingAs($this->coletor)
            ->postJson('/api/v1/mobile/coletas', [
                'data_coleta' => '2026-05-01 10:00:00',
                'nome_bem' => 'Sítio Teste',
                'latitude' => -5.0892,
                'longitude' => -42.8016,
                'natureza' => NaturezaBem::ARQUEOLOGICO->value,
                'versao' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['nome_bem' => 'Sítio Teste']);

        $this->assertDatabaseHas('coletas', [
            'nome_bem' => 'Sítio Teste',
            'usuario_id' => $this->coletor->id,
        ]);
    }

    public function test_coletor_nao_pode_ver_coleta_de_outro_usuario(): void
    {
        $outro = User::factory()->create(['ativo' => true]);
        $coleta = Coleta::factory()->create(['usuario_id' => $outro->id]);

        $this->actingAs($this->coletor)
            ->getJson("/api/v1/mobile/coletas/{$coleta->id}")
            ->assertStatus(403);
    }

    public function test_coletor_pode_ver_proprias_coletas(): void
    {
        Coleta::factory()->count(3)->create(['usuario_id' => $this->coletor->id]);

        $this->actingAs($this->coletor)
            ->getJson('/api/v1/mobile/coletas')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_soft_delete_nao_retorna_coleta_deletada(): void
    {
        $coleta = Coleta::factory()->create([
            'usuario_id' => $this->coletor->id,
            'deletado_em' => now(),
        ]);

        $this->actingAs($this->coletor)
            ->getJson('/api/v1/mobile/coletas')
            ->assertJsonMissing(['id' => $coleta->id]);
    }
}
