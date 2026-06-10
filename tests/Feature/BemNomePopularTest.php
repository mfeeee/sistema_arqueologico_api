<?php

namespace Tests\Feature;

use App\Models\BemMaterial;
use App\Models\BemNomePopular;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BemNomePopularTest extends TestCase
{
    use RefreshDatabase;

    public function test_pode_criar_nome_popular_para_bem_material(): void
    {
        $nomePopular = BemNomePopular::factory()->create(['nome' => 'Pedra Furada']);

        $this->assertDatabaseHas('bem_nomes_populares', [
            'id' => $nomePopular->id,
            'bem_material_id' => $nomePopular->bem_material_id,
            'nome' => 'Pedra Furada',
        ]);
    }

    public function test_relacao_nome_popular_retorna_bem_material(): void
    {
        $bem = BemMaterial::factory()->create();
        $nomePopular = BemNomePopular::factory()->create(['bem_material_id' => $bem->id]);

        $this->assertTrue($nomePopular->bemMaterial->is($bem));
    }

    public function test_relacao_bem_material_retorna_nomes_populares(): void
    {
        $bem = BemMaterial::factory()->create();
        BemNomePopular::factory()->count(3)->create(['bem_material_id' => $bem->id]);

        $this->assertCount(3, $bem->nomesPopulares);
    }

    public function test_multiplos_nomes_populares_sao_persistidos_e_recuperados_na_ordem_de_criacao(): void
    {
        $bem = BemMaterial::factory()->create();

        BemNomePopular::create(['bem_material_id' => $bem->id, 'nome' => 'Pedra Furada']);
        BemNomePopular::create(['bem_material_id' => $bem->id, 'nome' => 'Serra da Capivara I']);
        BemNomePopular::create(['bem_material_id' => $bem->id, 'nome' => 'Boqueirão Grande']);

        $nomes = $bem->nomesPopulares()->pluck('nome')->all();

        $this->assertSame(['Pedra Furada', 'Serra da Capivara I', 'Boqueirão Grande'], $nomes);
    }

    public function test_cascade_deleta_nomes_populares_ao_remover_bem_material(): void
    {
        $nomePopular = BemNomePopular::factory()->create();
        $bemId = $nomePopular->bem_material_id;

        $nomePopular->bemMaterial->forceDelete();

        $this->assertDatabaseMissing('bem_nomes_populares', ['bem_material_id' => $bemId]);
    }
}
