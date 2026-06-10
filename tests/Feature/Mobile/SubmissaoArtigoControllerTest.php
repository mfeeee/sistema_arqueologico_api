<?php

namespace Tests\Feature\Mobile;

use App\Enums\PerfilUsuario;
use App\Enums\TipoMencaoArtigo;
use App\Models\ArtigoAutor;
use App\Models\ArtigoCientifico;
use App\Models\BemMaterial;
use App\Models\SubmissaoArtigo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissaoArtigoControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::COLETOR]);
    }

    public function test_pode_criar_submissao_de_artigo_novo_com_autores(): void
    {
        $bem = BemMaterial::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/mobile/submissoes-artigos', [
                'bem_material_id' => $bem->id,
                'doi' => '10.1234/poti-2026',
                'titulo' => 'Arqueologia do Cânion do Poti',
                'autores' => ['Fogaça, E.', 'Guidon, N.'],
                'tipo_mencao' => TipoMencaoArtigo::CITACAO->value,
            ]);

        $response->assertStatus(201);

        $submissao = SubmissaoArtigo::where('doi', '10.1234/poti-2026')->firstOrFail();

        $this->assertDatabaseHas('submissao_autores', [
            'submissao_id' => $submissao->id,
            'nome_autor' => 'Fogaça, E.',
            'ordem' => 0,
        ]);
        $this->assertDatabaseHas('submissao_autores', [
            'submissao_id' => $submissao->id,
            'nome_autor' => 'Guidon, N.',
            'ordem' => 1,
        ]);

        $response->assertJsonPath('autores.0.nome_autor', 'Fogaça, E.')
            ->assertJsonPath('autores.1.nome_autor', 'Guidon, N.');
    }

    public function test_submissao_de_artigo_novo_exige_titulo_e_autores(): void
    {
        $bem = BemMaterial::factory()->create();

        $this->actingAs($this->user)
            ->postJson('/api/v1/mobile/submissoes-artigos', [
                'bem_material_id' => $bem->id,
                'tipo_mencao' => TipoMencaoArtigo::CITACAO->value,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['titulo', 'autores']);
    }

    public function test_pode_criar_submissao_de_artigo_existente_sem_autores_proprios(): void
    {
        $artigo = ArtigoCientifico::factory()->create();
        ArtigoAutor::factory()->count(2)->sequence(
            ['nome_autor' => 'Pessis, A.-M.', 'ordem' => 0],
            ['nome_autor' => 'Guidon, N.', 'ordem' => 1],
        )->create(['artigo_id' => $artigo->id]);

        $bem = BemMaterial::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/mobile/submissoes-artigos', [
                'bem_material_id' => $bem->id,
                'artigo_id' => $artigo->id,
                'tipo_mencao' => TipoMencaoArtigo::CITACAO->value,
            ]);

        $response->assertStatus(201);

        $submissao = SubmissaoArtigo::where('artigo_id', $artigo->id)->firstOrFail();

        $this->assertDatabaseMissing('submissao_autores', ['submissao_id' => $submissao->id]);

        $response->assertJsonPath('artigo.autores.0.nome_autor', 'Pessis, A.-M.')
            ->assertJsonPath('artigo.autores.1.nome_autor', 'Guidon, N.');
    }
}
