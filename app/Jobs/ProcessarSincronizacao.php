<?php

namespace App\Jobs;

use App\Actions\RegistrarColetaAction;
use App\Enums\StatusColeta;
use App\Exceptions\SincronizacaoException;
use App\Models\Coleta;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ProcessarSincronizacao implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public int $timeout = 120;

    /**
     * @param  list<array<string, mixed>>  $coletas
     */
    public function __construct(
        private readonly string $usuarioId,
        private readonly array $coletas,
    ) {}

    public function handle(RegistrarColetaAction $registrar): void
    {
        $usuario = User::find($this->usuarioId);

        if ($usuario === null) {
            return; // conta excluída ou anonimizada depois do envio
        }

        foreach ($this->coletas as $dados) {
            try {
                $registrar->execute($usuario, $dados);
            } catch (SincronizacaoException $e) {
                Log::warning('Coleta recusada na sincronização', [
                    'coleta_id' => $dados['id'] ?? null,
                    'usuario_id' => $this->usuarioId,
                    'motivo' => $e->getMessage(),
                ]);
                $this->marcarComoConflito($dados['id'] ?? null);
            }
            // Outros erros sobem: o job tenta de novo ($tries) e, no fim, failed() registra.
        }
    }

    private function marcarComoConflito(?string $coletaId): void
    {
        if ($coletaId === null) {
            return;
        }

        // Só marca coleta do próprio usuário: um id alheio não pode virar "conflito".
        Coleta::where('id', $coletaId)
            ->where('usuario_id', $this->usuarioId)
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
