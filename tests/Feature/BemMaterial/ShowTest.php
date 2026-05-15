<?php

namespace Tests\Feature\BemMaterial;

use App\Enums\PerfilUsuario;
use App\Models\BemMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::ADMIN]);
    }

    public function test_show_retorna_o_bem_completo_com_relacoes(): void
    {
        $bem = BemMaterial::factory()->publicado()->create([
            'nome_bem' => 'Sítio do exemplo',
            'uf' => 'PI',
            'municipio' => 'Teresina',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/mobile/bens-materiais/'.$bem->id);

        $response->assertOk()
            ->assertJsonPath('id', $bem->id)
            ->assertJsonPath('nome_bem', 'Sítio do exemplo')
            ->assertJsonPath('uf', 'PI')
            ->assertJsonPath('municipio', 'Teresina')
            ->assertJsonPath('publicado', true)
            ->assertJsonStructure([
                'midias',
                'responsavel',
            ]);
    }
}
