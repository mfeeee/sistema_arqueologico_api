<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\BemMaterial;
use App\Models\Coleta;
use App\Models\Curadoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcervoCorrigirDadosTest extends TestCase
{
    use RefreshDatabase;

    public function test_nao_manda_coletas_para_a_curadoria_e_pode_rodar_de_novo(): void
    {
        $semFila = Coleta::factory()->count(2)->create();
        $comFila = Coleta::factory()->create();
        Curadoria::factory()->create(['entidade_tipo' => 'coleta', 'entidade_id' => $comFila->id]);

        $this->artisan('acervo:corrigir-dados')->assertSuccessful();
        $this->artisan('acervo:corrigir-dados')->assertSuccessful();

        // Quem envia para curadoria é o coletor, pelo app.
        foreach ($semFila as $coleta) {
            $this->assertSame(0, Curadoria::where('entidade_tipo', 'coleta')->where('entidade_id', $coleta->id)->count());
        }
        $this->assertSame(1, Curadoria::where('entidade_tipo', 'coleta')->where('entidade_id', $comFila->id)->count());
    }

    public function test_simulacao_nao_grava_nada(): void
    {
        Coleta::factory()->create();

        $this->artisan('acervo:corrigir-dados', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(0, Curadoria::count());
    }

    public function test_preenche_curador_responsavel_e_migra_nomes_populares(): void
    {
        $curador = User::factory()->curador()->create();
        $bem = BemMaterial::factory()->create([
            'curador_responsavel_id' => null,
            'nomes_populares' => 'Toca da Onça; Pedra Furada',
        ]);
        Auditoria::factory()->insercao()->create([
            'entidade_id' => $bem->id,
            'usuario_id' => $curador->id,
            'meio' => 'Curadoria',
        ]);

        $this->artisan('acervo:corrigir-dados')->assertSuccessful();

        $this->assertSame($curador->id, $bem->fresh()->curador_responsavel_id);
        $this->assertEqualsCanonicalizing(['Toca da Onça', 'Pedra Furada'], $bem->nomesPopulares()->pluck('nome')->all());
    }
}
