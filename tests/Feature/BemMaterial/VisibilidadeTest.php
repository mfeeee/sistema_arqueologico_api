<?php

namespace Tests\Feature\BemMaterial;

use App\Models\BemMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Plano de correções, P25 (decisão D1): por padrão só aparecem sítios publicados,
 * mas qualquer pessoa pode pedir os não publicados para ajudar a completar as informações.
 */
class VisibilidadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_padrao_so_mostra_publicados(): void
    {
        $publicado = BemMaterial::factory()->publicado()->create();
        $naoPublicado = BemMaterial::factory()->naoPublicado()->create();

        $ids = collect($this->getJson('/api/v1/mobile/bens-materiais')->assertOk()->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($publicado->id));
        $this->assertFalse($ids->contains($naoPublicado->id));
    }

    public function test_visitante_pode_pedir_os_nao_publicados_mas_nunca_os_excluidos(): void
    {
        $naoPublicado = BemMaterial::factory()->naoPublicado()->create();
        $excluido = BemMaterial::factory()->naoPublicado()->create();
        $excluido->delete();

        $ids = collect($this->getJson('/api/v1/mobile/bens-materiais?publicado=all')->assertOk()->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($naoPublicado->id));
        $this->assertFalse($ids->contains($excluido->id));
    }

    public function test_visitante_abre_sitio_nao_publicado_mas_nao_o_excluido(): void
    {
        $naoPublicado = BemMaterial::factory()->naoPublicado()->create();
        $excluido = BemMaterial::factory()->naoPublicado()->create();
        $excluido->delete();

        $this->getJson("/api/v1/mobile/bens-materiais/{$naoPublicado->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $naoPublicado->id)
            ->assertJsonPath('data.publicado', false);

        $this->getJson("/api/v1/mobile/bens-materiais/{$excluido->id}")->assertNotFound();
    }
}
