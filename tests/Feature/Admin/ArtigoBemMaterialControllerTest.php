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

class ArtigoBemMaterialControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $curador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->curador = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::CURADOR]);
    }

    public function test_curador_pode_remover_vinculo_e_gera_auditoria_com_autores(): void
    {
        $artigo = ArtigoCientifico::factory()->create();
        ArtigoAutor::factory()->count(2)->sequence(
            ['nome_autor' => 'Pessis, A.-M.', 'ordem' => 0],
            ['nome_autor' => 'Guidon, N.', 'ordem' => 1],
        )->create(['artigo_id' => $artigo->id]);

        $bem = BemMaterial::factory()->create();

        $vinculo = ArtigoBemMaterial::create([
            'artigo_id' => $artigo->id,
            'bem_material_id' => $bem->id,
            'tipo_mencao' => TipoMencaoArtigo::CITACAO,
            'trecho_relevante' => 'Trecho de teste.',
        ]);

        $this->actingAs($this->curador)
            ->deleteJson("/api/v1/admin/artigos-bem-material/{$vinculo->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('artigo_bem_material', ['id' => $vinculo->id]);
        $this->assertDatabaseHas('artigos_cientificos', ['id' => $artigo->id]);

        $auditoria = Auditoria::where('entidade_tipo', ArtigoBemMaterial::class)
            ->where('entidade_id', $vinculo->id)
            ->firstOrFail();

        $this->assertSame(['Pessis, A.-M.', 'Guidon, N.'], $auditoria->valor_anterior['artigo_autores']);
    }
}
