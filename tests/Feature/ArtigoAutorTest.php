<?php

namespace Tests\Feature;

use App\Models\ArtigoAutor;
use App\Models\ArtigoCientifico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtigoAutorTest extends TestCase
{
    use RefreshDatabase;

    public function test_pode_criar_artigo_cientifico(): void
    {
        $artigo = ArtigoCientifico::factory()->create(['titulo' => 'Revisão estratigráfica do Sítio X']);

        $this->assertDatabaseHas('artigos_cientificos', [
            'id' => $artigo->id,
            'titulo' => 'Revisão estratigráfica do Sítio X',
        ]);
    }

    public function test_pode_criar_autor_para_artigo(): void
    {
        $artigo = ArtigoCientifico::factory()->create();

        $autor = ArtigoAutor::factory()->create([
            'artigo_id' => $artigo->id,
            'nome_autor' => 'Guidon, N.',
            'ordem' => 0,
        ]);

        $this->assertDatabaseHas('artigo_autores', [
            'id' => $autor->id,
            'artigo_id' => $artigo->id,
            'nome_autor' => 'Guidon, N.',
            'ordem' => 0,
        ]);
    }

    public function test_relacao_autor_retorna_artigo(): void
    {
        $artigo = ArtigoCientifico::factory()->create();
        $autor = ArtigoAutor::factory()->create(['artigo_id' => $artigo->id]);

        $this->assertTrue($autor->artigo->is($artigo));
    }

    public function test_relacao_artigo_retorna_multiplos_autores(): void
    {
        $artigo = ArtigoCientifico::factory()->create();
        ArtigoAutor::factory()->count(3)->sequence(
            ['nome_autor' => 'Pessis, A.-M.', 'ordem' => 0],
            ['nome_autor' => 'Guidon, N.', 'ordem' => 1],
            ['nome_autor' => 'Boëda, E.', 'ordem' => 2],
        )->create(['artigo_id' => $artigo->id]);

        $this->assertCount(3, $artigo->autores);
    }

    public function test_relacao_artigo_retorna_autores_ordenados_por_ordem(): void
    {
        $artigo = ArtigoCientifico::factory()->create();

        // Cria fora de ordem para garantir que a ordenação não dependa da ordem de inserção.
        ArtigoAutor::factory()->create(['artigo_id' => $artigo->id, 'nome_autor' => 'Boëda, E.', 'ordem' => 2]);
        ArtigoAutor::factory()->create(['artigo_id' => $artigo->id, 'nome_autor' => 'Pessis, A.-M.', 'ordem' => 0]);
        ArtigoAutor::factory()->create(['artigo_id' => $artigo->id, 'nome_autor' => 'Guidon, N.', 'ordem' => 1]);

        $nomes = $artigo->autores->pluck('nome_autor')->all();

        $this->assertSame(['Pessis, A.-M.', 'Guidon, N.', 'Boëda, E.'], $nomes);
    }

    public function test_cascade_deleta_autores_ao_remover_artigo(): void
    {
        $artigo = ArtigoCientifico::factory()->create();
        ArtigoAutor::factory()->count(2)->create(['artigo_id' => $artigo->id]);

        $artigo->delete();

        $this->assertDatabaseMissing('artigo_autores', ['artigo_id' => $artigo->id]);
    }
}
