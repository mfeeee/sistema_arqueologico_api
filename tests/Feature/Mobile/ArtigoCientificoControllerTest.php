<?php

namespace Tests\Feature\Mobile;

use App\Enums\PerfilUsuario;
use App\Enums\TipoMencaoArtigo;
use App\Models\ArtigoAutor;
use App\Models\ArtigoBemMaterial;
use App\Models\ArtigoCientifico;
use App\Models\BemMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtigoCientificoControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::COLETOR]);
    }

    public function test_por_bem_material_retorna_autores_do_artigo(): void
    {
        $artigo = ArtigoCientifico::factory()->create();
        ArtigoAutor::factory()->count(2)->sequence(
            ['nome_autor' => 'Fogaça, E.', 'ordem' => 0],
            ['nome_autor' => 'Guidon, N.', 'ordem' => 1],
        )->create(['artigo_id' => $artigo->id]);

        $bem = BemMaterial::factory()->create();

        ArtigoBemMaterial::create([
            'artigo_id' => $artigo->id,
            'bem_material_id' => $bem->id,
            'tipo_mencao' => TipoMencaoArtigo::CITACAO,
            'trecho_relevante' => 'Trecho de teste.',
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/v1/mobile/bens-materiais/{$bem->id}/artigos")
            ->assertStatus(200)
            ->assertJsonPath('artigos.0.autores', ['Fogaça, E.', 'Guidon, N.']);
    }

    public function test_buscar_por_doi_retorna_autores_do_artigo(): void
    {
        $artigo = ArtigoCientifico::factory()->create(['doi' => '10.9999/poti']);
        ArtigoAutor::factory()->count(2)->sequence(
            ['nome_autor' => 'Pessis, A.-M.', 'ordem' => 0],
            ['nome_autor' => 'Guidon, N.', 'ordem' => 1],
        )->create(['artigo_id' => $artigo->id]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/mobile/artigos-cientificos/buscar-doi?doi='.urlencode('10.9999/poti'))
            ->assertStatus(200)
            ->assertJsonPath('artigo.id', $artigo->id)
            ->assertJsonPath('artigo.autores', ['Pessis, A.-M.', 'Guidon, N.']);
    }

    public function test_buscar_por_doi_retorna_null_quando_nao_encontrado(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/v1/mobile/artigos-cientificos/buscar-doi?doi='.urlencode('10.0000/inexistente'))
            ->assertStatus(200)
            ->assertJsonPath('artigo', null);
    }
}
