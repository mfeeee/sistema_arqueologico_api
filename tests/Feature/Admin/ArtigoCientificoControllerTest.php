<?php

namespace Tests\Feature\Admin;

use App\Enums\PerfilUsuario;
use App\Enums\TipoMencaoArtigo;
use App\Models\ArtigoAutor;
use App\Models\ArtigoBemMaterial;
use App\Models\ArtigoCientifico;
use App\Models\Auditoria;
use App\Models\BemMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtigoCientificoControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $curador;

    private User $coletor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::ADMIN]);
        $this->curador = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::CURADOR]);
        $this->coletor = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::COLETOR]);
    }

    // ── autorização ───────────────────────────────────────────────────────────

    public function test_coletor_nao_pode_listar_artigos_admin(): void
    {
        $this->actingAs($this->coletor)
            ->getJson('/api/v1/admin/artigos-cientificos')
            ->assertStatus(403);
    }

    public function test_nao_autenticado_nao_pode_listar_artigos_admin(): void
    {
        $this->getJson('/api/v1/admin/artigos-cientificos')
            ->assertStatus(401);
    }

    // ── index ─────────────────────────────────────────────────────────────────

    public function test_curador_pode_listar_artigos_com_contagem_de_vinculos(): void
    {
        $artigo = ArtigoCientifico::factory()->create(['titulo' => 'Estudo Pedra Furada']);
        ArtigoAutor::factory()->create(['artigo_id' => $artigo->id, 'nome_autor' => 'Guidon, N.', 'ordem' => 0]);

        $bem = BemMaterial::factory()->create();
        ArtigoBemMaterial::create([
            'artigo_id' => $artigo->id,
            'bem_material_id' => $bem->id,
            'tipo_mencao' => TipoMencaoArtigo::CITACAO,
        ]);

        $this->actingAs($this->curador)
            ->getJson('/api/v1/admin/artigos-cientificos')
            ->assertStatus(200)
            ->assertJsonPath('data.0.titulo', 'Estudo Pedra Furada')
            ->assertJsonPath('data.0.vinculos_count', 1);
    }

    public function test_index_retorna_autores_do_artigo(): void
    {
        $artigo = ArtigoCientifico::factory()->create();
        ArtigoAutor::factory()->count(2)->sequence(
            ['nome_autor' => 'Pessis, A.-M.', 'ordem' => 0],
            ['nome_autor' => 'Guidon, N.', 'ordem' => 1],
        )->create(['artigo_id' => $artigo->id]);

        $response = $this->actingAs($this->curador)
            ->getJson('/api/v1/admin/artigos-cientificos')
            ->assertStatus(200);

        $autores = $response->json('data.0.autores');
        $this->assertCount(2, $autores);
        $this->assertSame('Pessis, A.-M.', $autores[0]['nome_autor']);
    }

    public function test_index_retorna_lista_vazia_quando_sem_artigos(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/artigos-cientificos')
            ->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    // ── show ──────────────────────────────────────────────────────────────────

    public function test_curador_pode_ver_detalhe_do_artigo_com_vinculos(): void
    {
        $artigo = ArtigoCientifico::factory()->create();
        ArtigoAutor::factory()->create(['artigo_id' => $artigo->id, 'nome_autor' => 'Fogaça, E.', 'ordem' => 0]);

        $bem = BemMaterial::factory()->create(['nome_bem' => 'Sítio Lajeado']);
        ArtigoBemMaterial::create([
            'artigo_id' => $artigo->id,
            'bem_material_id' => $bem->id,
            'tipo_mencao' => TipoMencaoArtigo::CITACAO,
        ]);

        $this->actingAs($this->curador)
            ->getJson("/api/v1/admin/artigos-cientificos/{$artigo->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $artigo->id)
            ->assertJsonPath('data.vinculos_count', 1)
            ->assertJsonStructure(['data' => ['autores', 'vinculos']]);
    }

    public function test_show_retorna_404_para_artigo_inexistente(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/artigos-cientificos/99999')
            ->assertStatus(404);
    }

    public function test_show_inclui_bem_material_de_cada_vinculo(): void
    {
        $artigo = ArtigoCientifico::factory()->create();
        $bem = BemMaterial::factory()->create(['nome_bem' => 'Sítio Serra da Capivara']);

        ArtigoBemMaterial::create([
            'artigo_id' => $artigo->id,
            'bem_material_id' => $bem->id,
            'tipo_mencao' => TipoMencaoArtigo::CITACAO,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/artigos-cientificos/{$artigo->id}")
            ->assertStatus(200);

        $this->assertSame('Sítio Serra da Capivara', $response->json('data.vinculos.0.bem_material.nome_bem'));
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function test_curador_pode_excluir_artigo_e_seus_vinculos(): void
    {
        $artigo = ArtigoCientifico::factory()->create();
        ArtigoAutor::factory()->create(['artigo_id' => $artigo->id]);
        $bem = BemMaterial::factory()->create();
        ArtigoBemMaterial::create([
            'artigo_id' => $artigo->id,
            'bem_material_id' => $bem->id,
            'tipo_mencao' => TipoMencaoArtigo::CITACAO,
        ]);

        $this->actingAs($this->curador)
            ->deleteJson("/api/v1/admin/artigos-cientificos/{$artigo->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('artigos_cientificos', ['id' => $artigo->id]);
        $this->assertDatabaseMissing('artigo_bem_material', ['artigo_id' => $artigo->id]);
    }

    public function test_destroy_gera_auditoria_de_exclusao(): void
    {
        $artigo = ArtigoCientifico::factory()->create(['titulo' => 'Artigo Excluído']);
        ArtigoAutor::factory()->count(2)->sequence(
            ['nome_autor' => 'Pessis, A.-M.', 'ordem' => 0],
            ['nome_autor' => 'Guidon, N.', 'ordem' => 1],
        )->create(['artigo_id' => $artigo->id]);

        $bem = BemMaterial::factory()->create();
        ArtigoBemMaterial::create([
            'artigo_id' => $artigo->id,
            'bem_material_id' => $bem->id,
            'tipo_mencao' => TipoMencaoArtigo::CITACAO,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/artigos-cientificos/{$artigo->id}")
            ->assertStatus(204);

        $auditoria = Auditoria::where('entidade_tipo', ArtigoCientifico::class)
            ->where('entidade_id', $artigo->id)
            ->where('operacao', 'Exclusão')
            ->firstOrFail();

        $this->assertSame('Artigo Excluído', $auditoria->valor_anterior['titulo']);
        $this->assertSame(['Pessis, A.-M.', 'Guidon, N.'], $auditoria->valor_anterior['autores']);
        $this->assertSame(1, $auditoria->valor_anterior['total_vinculos_removidos']);
        $this->assertNull($auditoria->valor_novo);
    }

    public function test_destroy_retorna_404_para_artigo_inexistente(): void
    {
        $this->actingAs($this->admin)
            ->deleteJson('/api/v1/admin/artigos-cientificos/99999')
            ->assertStatus(404);
    }

    public function test_coletor_nao_pode_excluir_artigo(): void
    {
        $artigo = ArtigoCientifico::factory()->create();

        $this->actingAs($this->coletor)
            ->deleteJson("/api/v1/admin/artigos-cientificos/{$artigo->id}")
            ->assertStatus(403);
    }
}
