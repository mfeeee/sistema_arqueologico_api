<?php

namespace App\Jobs;

use App\Enums\StatusColeta;
use App\Exceptions\SincronizacaoException;
use App\Models\Coleta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessarSincronizacao implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30; 

    public int $timeout = 120;

    public function __construct(
        private readonly string $usuarioId,
        private readonly array $coletas,
    ) {}

    public function handle(): void
    {
        foreach ($this->coletas as $dados) {
            try {
                DB::transaction(function () use ($dados) {
                    $this->upsertColeta($dados);
                });
            } catch (\Throwable $e) {
                Log::error('Falha ao sincronizar coleta', [
                    'coleta_id' => $dados['id'] ?? null,
                    'usuario_id' => $this->usuarioId,
                    'error' => $e->getMessage(),
                ]);

                $this->marcarComoConflito($dados['id'] ?? null);
            }
        }
    }

    private function upsertColeta(array $dados): void
    {
        $existente = Coleta::find($dados['id']);

        if ($existente && $existente->versao > $dados['versao']) {

            $existente->update(['status_sincronizacao' => StatusColeta::CONFLITO]);
            throw new SincronizacaoException(
                "Conflito de versão na coleta {$dados['id']}: servidor={$existente->versao}, cliente={$dados['versao']}"
            );
        }

        Coleta::updateOrCreate(
            ['id' => $dados['id']],
            [
                ...$dados,
                'usuario_id' => $this->usuarioId,
                'status_sincronizacao' => StatusColeta::SINCRONIZADO,
            ]
        );
    }

    private function marcarComoConflito(?string $coletaId): void
    {
        if ($coletaId === null) {
            return;
        }

        Coleta::where('id', $coletaId)
            ->update(['status_sincronizacao' => StatusColeta::CONFLITO]);
    }

    public function failed(\Throwable $e): void
    {
        Log::critical('Job ProcessarSincronizacao falhou após todas as tentativas', [
            'usuario_id' => $this->usuarioId,
            'error' => $e->getMessage(),
        ]);
    }
}
