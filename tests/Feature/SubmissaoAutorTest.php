<?php

namespace Tests\Feature;

use App\Models\ArtigoAutor;
use App\Models\ArtigoCientifico;
use App\Models\SubmissaoArtigo;
use App\Models\SubmissaoAutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissaoAutorTest extends TestCase
{
    use RefreshDatabase;

    public function test_pode_criar_submissao_de_artigo(): void
    {
        $submissao = SubmissaoArtigo::factory()->create(['titulo' => 'Arqueologia do Cânion do Poti']);

        $this->assertDatabaseHas('submissoes_artigos', [
            'id' => $submissao->id,
            'titulo' => 'Arqueologia do Cânion do Poti',
            'status' => 'pendente',
        ]);
    }

    public function test_pode_criar_autor_para_submissao(): void
    {
        $submissao = SubmissaoArtigo::factory()->create();

        $autor = SubmissaoAutor::factory()->create([
            'submissao_id' => $submissao->id,
            'nome_autor' => 'Fogaça, E.',
            'ordem' => 0,
        ]);

        $this->assertDatabaseHas('submissao_autores', [
            'id' => $autor->id,
            'submissao_id' => $submissao->id,
            'nome_autor' => 'Fogaça, E.',
            'ordem' => 0,
        ]);
    }

    public function test_relacao_autor_retorna_submissao(): void
    {
        $submissao = SubmissaoArtigo::factory()->create();
        $autor = SubmissaoAutor::factory()->create(['submissao_id' => $submissao->id]);

        $this->assertTrue($autor->submissao->is($submissao));
    }

    public function test_relacao_submissao_retorna_multiplos_autores(): void
    {
        $submissao = SubmissaoArtigo::factory()->create();
        SubmissaoAutor::factory()->count(2)->sequence(
            ['nome_autor' => 'Fogaça, E.', 'ordem' => 0],
            ['nome_autor' => 'Guidon, N.', 'ordem' => 1],
        )->create(['submissao_id' => $submissao->id]);

        $this->assertCount(2, $submissao->autores);
    }

    public function test_relacao_submissao_retorna_autores_ordenados_por_ordem(): void
    {
        $submissao = SubmissaoArtigo::factory()->create();

        // Cria fora de ordem para garantir que a ordenação não dependa da ordem de inserção.
        SubmissaoAutor::factory()->create(['submissao_id' => $submissao->id, 'nome_autor' => 'Guidon, N.', 'ordem' => 1]);
        SubmissaoAutor::factory()->create(['submissao_id' => $submissao->id, 'nome_autor' => 'Fogaça, E.', 'ordem' => 0]);

        $nomes = $submissao->autores->pluck('nome_autor')->all();

        $this->assertSame(['Fogaça, E.', 'Guidon, N.'], $nomes);
    }

    public function test_cascade_deleta_autores_ao_remover_submissao(): void
    {
        $submissao = SubmissaoArtigo::factory()->create();
        SubmissaoAutor::factory()->count(2)->create(['submissao_id' => $submissao->id]);

        $submissao->delete();

        $this->assertDatabaseMissing('submissao_autores', ['submissao_id' => $submissao->id]);
    }

    /**
     * Cenário B (artigo novo): a submissão carrega seus próprios autores em
     * submissao_autores, já que ainda não existe um ArtigoCientifico relacionado.
     */
    public function test_submissao_de_artigo_novo_possui_autores_proprios(): void
    {
        $submissao = SubmissaoArtigo::factory()->create(['artigo_id' => null]);
        SubmissaoAutor::factory()->count(2)->sequence(
            ['nome_autor' => 'Fogaça, E.', 'ordem' => 0],
            ['nome_autor' => 'Guidon, N.', 'ordem' => 1],
        )->create(['submissao_id' => $submissao->id]);

        $this->assertNull($submissao->artigo_id);
        $this->assertSame(['Fogaça, E.', 'Guidon, N.'], $submissao->autores->pluck('nome_autor')->all());
    }

    /**
     * Cenário A (artigo já existente): a submissão referencia um artigo_id e
     * os autores definitivos vêm de artigo_autores via a relação artigo->autores,
     * mantendo consistência com o fluxo de artigo final.
     */
    public function test_submissao_com_artigo_existente_usa_autores_do_artigo_relacionado(): void
    {
        $artigo = ArtigoCientifico::factory()->create();
        ArtigoAutor::factory()->count(2)->sequence(
            ['nome_autor' => 'Pessis, A.-M.', 'ordem' => 0],
            ['nome_autor' => 'Guidon, N.', 'ordem' => 1],
        )->create(['artigo_id' => $artigo->id]);

        $submissao = SubmissaoArtigo::factory()->create([
            'artigo_id' => $artigo->id,
            'titulo' => null,
            'doi' => $artigo->doi,
        ]);

        $this->assertCount(0, $submissao->autores);
        $this->assertSame(['Pessis, A.-M.', 'Guidon, N.'], $submissao->artigo->autores->pluck('nome_autor')->all());
    }
}
