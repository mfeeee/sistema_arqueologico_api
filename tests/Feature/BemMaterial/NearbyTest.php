<?php

namespace Tests\Feature\BemMaterial;

use App\Enums\NaturezaBem;
use App\Enums\PerfilUsuario;
use App\Enums\TipoBem;
use App\Models\BemMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NearbyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::COLETOR]);
    }

    public function test_retorna_sitios_dentro_do_raio(): void
    {
        // Teresina-PI: -5.0892, -42.8016
        $bemProximo = BemMaterial::factory()->create([
            'publicado' => true,
            'latitude'  => -5.0900,
            'longitude' => -42.8020,
        ]);

        // Fortaleza-CE: ~500km de distância
        BemMaterial::factory()->create([
            'publicado' => true,
            'latitude'  => -3.7172,
            'longitude' => -38.5433,
        ]);

        // Seta o geom via PostGIS para o bem próximo
        DB::statement(
            "UPDATE bens_materiais SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?",
            [$bemProximo->longitude, $bemProximo->latitude, $bemProximo->id]
        );

        $response = $this->actingAs($this->user)
            ->getJson('/api/bens-materiais/nearby?latitude=-5.0892&longitude=-42.8016&raio_km=5');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $bemProximo->id]);
    }

    public function test_nearby_exige_latitude_e_longitude(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/bens-materiais/nearby')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_nearby_nao_retorna_sitios_nao_publicados(): void
    {
        $bemPrivado = BemMaterial::factory()->create([
            'publicado' => false,
            'latitude'  => -5.0900,
            'longitude' => -42.8020,
        ]);

        DB::statement(
            "UPDATE bens_materiais SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?",
            [$bemPrivado->longitude, $bemPrivado->latitude, $bemPrivado->id]
        );

        $this->actingAs($this->user)
            ->getJson('/api/bens-materiais/nearby?latitude=-5.0892&longitude=-42.8016&raio_km=5')
            ->assertStatus(200)
            ->assertJsonCount(0);
    }
}