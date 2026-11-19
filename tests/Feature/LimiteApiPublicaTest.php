<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Plano de correções, P23: o site web não divide o limite de 30/min de um visitante.
 */
class LimiteApiPublicaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.web.client_key' => 'segredo-do-site']);
    }

    public function test_visitante_para_na_31a_requisicao(): void
    {
        $this->fazerRequisicoes(30, []);

        $this->getJson('/api/v1/mobile/bens-materiais')->assertTooManyRequests();
    }

    public function test_site_web_com_a_chave_tem_limite_proprio(): void
    {
        $this->fazerRequisicoes(30, ['X-Client-Key' => 'segredo-do-site']);

        $this->getJson('/api/v1/mobile/bens-materiais', ['X-Client-Key' => 'segredo-do-site'])->assertOk();
    }

    public function test_chave_errada_conta_como_visitante(): void
    {
        $this->fazerRequisicoes(30, ['X-Client-Key' => 'chute']);

        $this->getJson('/api/v1/mobile/bens-materiais', ['X-Client-Key' => 'chute'])->assertTooManyRequests();
    }

    /**
     * @param  array<string, string>  $cabecalhos
     */
    private function fazerRequisicoes(int $quantidade, array $cabecalhos): void
    {
        for ($i = 0; $i < $quantidade; $i++) {
            $this->getJson('/api/v1/mobile/bens-materiais', $cabecalhos)->assertOk();
        }
    }
}
