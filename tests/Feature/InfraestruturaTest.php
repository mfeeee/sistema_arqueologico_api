<?php

namespace Tests\Feature;

use App\Jobs\ProcessarSincronizacao;
use ReflectionClass;
use Tests\TestCase;

class InfraestruturaTest extends TestCase
{
    public function test_rota_publica_de_teste_da_fila_nao_existe(): void
    {
        $this->get('/test-queue')->assertNotFound();
    }

    public function test_retry_after_da_fila_e_maior_que_o_timeout_do_sync(): void
    {
        $timeoutDoSync = (new ReflectionClass(ProcessarSincronizacao::class))
            ->getProperty('timeout')
            ->getDefaultValue();

        $this->assertGreaterThan($timeoutDoSync, config('queue.connections.redis.retry_after'));
    }
}
