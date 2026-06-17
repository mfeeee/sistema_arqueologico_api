<?php

namespace Tests\Feature\BemMaterial;

use App\Enums\PerfilUsuario;
use App\Models\BemMaterial;
use App\Models\Localizacao;
use App\Models\Midia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');

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
            ->assertJsonPath('data.id', $bem->id)
            ->assertJsonPath('data.nome_bem', 'Sítio do exemplo')
            ->assertJsonPath('data.uf', 'PI')
            ->assertJsonPath('data.municipio', 'Teresina')
            ->assertJsonPath('data.publicado', true)
            ->assertJsonPath('data.localizacao_id', null)
            ->assertJsonStructure([
                'data' => [
                    'midias',
                    'responsaveis',
                ],
            ]);
    }

    public function test_show_retorna_localizacao_id_preenchido(): void
    {
        $localizacao = Localizacao::factory()->create([
            'municipio' => 'São Raimundo Nonato',
            'uf' => 'PI',
        ]);

        $bem = BemMaterial::factory()->publicado()->create([
            'localizacao_id' => $localizacao->id,
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/mobile/bens-materiais/'.$bem->id)
            ->assertOk()
            ->assertJsonPath('data.localizacao_id', $localizacao->id);
    }

    public function test_show_retorna_midias_vinculadas(): void
    {
        $bem = BemMaterial::factory()->publicado()->create();
        Midia::factory()->count(2)->create([
            'mediable_type' => BemMaterial::class,
            'mediable_id' => $bem->id,
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/mobile/bens-materiais/'.$bem->id)
            ->assertOk()
            ->assertJsonCount(2, 'data.midias');
    }

    public function test_show_retorna_midias_vazia_quando_sem_midias(): void
    {
        $bem = BemMaterial::factory()->publicado()->create();

        $this->actingAs($this->user)
            ->getJson('/api/v1/mobile/bens-materiais/'.$bem->id)
            ->assertOk()
            ->assertJsonPath('data.midias', []);
    }
}
