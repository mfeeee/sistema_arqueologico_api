<?php

namespace Tests\Feature\Curadoria;

use App\Enums\AcaoResultanteCuradoria;
use App\Enums\PerfilUsuario;
use App\Enums\StatusCuradoria;
use App\Models\ArtigoAutor;
use App\Models\ArtigoBemMaterial;
use App\Models\ArtigoCientifico;
use App\Models\Auditoria;
use App\Models\Curadoria;
use App\Models\SubmissaoArtigo;
use App\Models\SubmissaoAutor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvaliacaoSubmissaoArtigoTest extends TestCase
{
    use RefreshDatabase;

    private User $curador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->curador = User::factory()->create(['ativo' => true, 'perfil' => PerfilUsuario::CURADOR]);
    }

    public function test_curador_aprova_submissao_de_artigo_novo_cria_artigo_e_copia_autores(): void
    {
        $submissao = SubmissaoArtigo::factory()->create([
            'artigo_id' => null,
            'doi' => '10.5555/poti-novo',
            'titulo' => 'Estratigrafia do sítio Poti',
        ]);
        SubmissaoAutor::factory()->count(2)->sequence(
            ['nome_autor' => 'Fogaça, E.', 'ordem' => 0],
            ['nome_autor' => 'Guidon, N.', 'ordem' => 1],
        )->create(['submissao_id' => $submissao->id]);

        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'submissao_artigo',
            'entidade_id' => $submissao->id,
            'bem_material_id' => $submissao->bem_material_id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $response = $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::APROVAR->value,
                'observacao' => 'DOI verificado, artigo cadastrado.',
            ]);

        $response->assertStatus(200);

        $artigo = ArtigoCientifico::where('doi', '10.5555/poti-novo')->firstOrFail();

        $this->assertDatabaseHas('artigo_autores', [
            'artigo_id' => $artigo->id,
            'nome_autor' => 'Fogaça, E.',
            'ordem' => 0,
        ]);
        $this->assertDatabaseHas('artigo_autores', [
            'artigo_id' => $artigo->id,
            'nome_autor' => 'Guidon, N.',
            'ordem' => 1,
        ]);

        $this->assertDatabaseHas('submissoes_artigos', [
            'id' => $submissao->id,
            'artigo_id' => $artigo->id,
            'status' => 'aprovado',
        ]);

        $this->assertDatabaseHas('artigo_bem_material', [
            'artigo_id' => $artigo->id,
            'bem_material_id' => $submissao->bem_material_id,
        ]);

        $auditoria = Auditoria::where('entidade_tipo', ArtigoCientifico::class)
            ->where('entidade_id', $artigo->id)
            ->firstOrFail();

        $this->assertSame(['Fogaça, E.', 'Guidon, N.'], $auditoria->valor_novo['artigo_autores']);
    }

    public function test_curador_aprova_submissao_de_artigo_existente_cria_vinculo_com_autores_do_artigo(): void
    {
        $artigo = ArtigoCientifico::factory()->create();
        ArtigoAutor::factory()->count(2)->sequence(
            ['nome_autor' => 'Pessis, A.-M.', 'ordem' => 0],
            ['nome_autor' => 'Guidon, N.', 'ordem' => 1],
        )->create(['artigo_id' => $artigo->id]);

        $submissao = SubmissaoArtigo::factory()->create([
            'artigo_id' => $artigo->id,
            'titulo' => null,
        ]);

        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'submissao_artigo',
            'entidade_id' => $submissao->id,
            'bem_material_id' => $submissao->bem_material_id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $response = $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::APROVAR->value,
            ]);

        $response->assertStatus(200);

        $vinculo = ArtigoBemMaterial::where('artigo_id', $artigo->id)
            ->where('bem_material_id', $submissao->bem_material_id)
            ->firstOrFail();

        $auditoria = Auditoria::where('entidade_tipo', ArtigoBemMaterial::class)
            ->where('entidade_id', $vinculo->id)
            ->firstOrFail();

        $this->assertSame(['Pessis, A.-M.', 'Guidon, N.'], $auditoria->valor_novo['artigo_autores']);
    }

    public function test_aprovar_submissao_quando_vinculo_ja_existe_atualiza_em_vez_de_duplicar(): void
    {
        $artigo = ArtigoCientifico::factory()->create();
        ArtigoAutor::factory()->create(['artigo_id' => $artigo->id, 'nome_autor' => 'Guidon, N.', 'ordem' => 0]);

        $submissao = SubmissaoArtigo::factory()->create([
            'artigo_id' => $artigo->id,
            'tipo_mencao' => 'citacao',
            'trecho_relevante' => 'Trecho original.',
        ]);

        // Vínculo já existe antes da aprovação
        ArtigoBemMaterial::create([
            'artigo_id' => $artigo->id,
            'bem_material_id' => $submissao->bem_material_id,
            'tipo_mencao' => 'citacao',
            'trecho_relevante' => 'Trecho original.',
        ]);

        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'submissao_artigo',
            'entidade_id' => $submissao->id,
            'bem_material_id' => $submissao->bem_material_id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::APROVADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::APROVAR->value,
            ])
            ->assertStatus(200);

        // Deve existir exatamente um vínculo, não dois
        $this->assertSame(
            1,
            ArtigoBemMaterial::where('artigo_id', $artigo->id)
                ->where('bem_material_id', $submissao->bem_material_id)
                ->count()
        );

        // Auditoria deve indicar 'Atualização', não 'Inserção'
        $auditoria = Auditoria::where('entidade_tipo', ArtigoBemMaterial::class)
            ->where('operacao', 'Atualização')
            ->first();

        $this->assertNotNull($auditoria, 'Esperava auditoria de Atualização');
    }

    public function test_curador_rejeita_submissao_de_artigo(): void
    {
        $submissao = SubmissaoArtigo::factory()->create(['artigo_id' => null]);

        $curadoria = Curadoria::factory()->create([
            'entidade_tipo' => 'submissao_artigo',
            'entidade_id' => $submissao->id,
            'bem_material_id' => $submissao->bem_material_id,
            'usuario_id' => $this->curador->id,
            'status' => StatusCuradoria::PENDENTE->value,
        ]);

        $this->actingAs($this->curador)
            ->patchJson("/api/v1/admin/curadorias/{$curadoria->id}/avaliar", [
                'status' => StatusCuradoria::REJEITADO->value,
                'acao_resultante' => AcaoResultanteCuradoria::REJEITAR->value,
                'observacao' => 'DOI inválido.',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('submissoes_artigos', [
            'id' => $submissao->id,
            'status' => 'rejeitado',
        ]);

        $this->assertDatabaseHas('auditorias', [
            'entidade_tipo' => Curadoria::class,
            'entidade_id' => $curadoria->id,
            'curadoria_id' => $curadoria->id,
            'operacao' => 'Rejeição',
        ]);
    }
}
