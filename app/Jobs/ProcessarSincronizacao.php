<?php

namespace App\Jobs;

use App\Enums\StatusColeta;
use App\Exceptions\SincronizacaoException;
use App\Models\Coleta;
use App\Models\Localizacao;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        // Extrai lat/lng do payload (campo raiz ou objeto localizacao aninhado)
        $locDados = $dados['localizacao'] ?? null;
        $lat = $dados['latitude'] ?? ($locDados['geom']['lat'] ?? null);
        $lng = $dados['longitude'] ?? ($locDados['geom']['lng'] ?? null);
        $localizacaoId = $existente?->localizacao_id;

        if ($lat !== null && $lng !== null) {
            $loc = Localizacao::updateOrCreate(
                ['id' => $locDados['id'] ?? Str::uuid()->toString()],
                [
                    'uf' => $locDados['uf'] ?? $dados['uf'] ?? null,
                    'municipio' => $locDados['municipio'] ?? null,
                    'cep' => $locDados['cep'] ?? null,
                    'logradouro' => $locDados['logradouro'] ?? null,
                ]
            );

            DB::statement(
                'UPDATE localizacoes SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
                [$lng, $lat, $loc->id]
            );

            $localizacaoId = $loc->id;
        }

        // Apenas campos escalares — sem objetos aninhados
        Coleta::updateOrCreate(
            ['id' => $dados['id']],
            [
                'usuario_id' => $this->usuarioId,
                'nome_bem' => $dados['nome_bem'] ?? null,
                'data_coleta' => $dados['data_coleta'] ?? null,
                'natureza_bem' => $dados['natureza'] ?? null,
                'tipo_bem' => $dados['tipo'] ?? null,
                'uf' => $dados['uf'] ?? null,
                'latitude' => $lat,
                'longitude' => $lng,
                'versao' => $dados['versao'] ?? 1,
                'dados_coletados' => $dados['dados_coletados'] ?? [],
                'status_sincronizacao' => StatusColeta::SINCRONIZADO,
                'localizacao_id' => $localizacaoId,
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
