<?php

namespace App\Jobs;

use App\Models\MidiaLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessarUploadMidia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private readonly string $bemMaterialId,
        private readonly string $tipoMidia,
        private readonly string $caminhoTemporario,
        private readonly string $nomeOriginal,
        private readonly ?string $descricao = null,
    ) {}

    public function handle(): void
    {
        try {
            if (! Storage::disk('local')->exists($this->caminhoTemporario)) {
                Log::warning('Arquivo temporário não encontrado', [
                    'caminho' => $this->caminhoTemporario,
                ]);

                return;
            }

            $extensao = pathinfo($this->nomeOriginal, PATHINFO_EXTENSION);
            $destino = "midias/{$this->bemMaterialId}/".Str::uuid().".{$extensao}";

            Storage::disk('public')->put(
                $destino,
                Storage::disk('local')->get($this->caminhoTemporario)
            );

            MidiaLink::create([
                'bem_material_id' => $this->bemMaterialId,
                'tipo' => $this->tipoMidia,
                'url' => Storage::disk('public')->url($destino),
                'descricao' => $this->descricao,
            ]);

            Storage::disk('local')->delete($this->caminhoTemporario);
        } catch (\Throwable $e) {
            Log::error('Falha no upload de mídia', [
                'bem_material_id' => $this->bemMaterialId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::critical('Job ProcessarUploadMidia falhou após todas as tentativas', [
            'bem_material_id' => $this->bemMaterialId,
            'error' => $e->getMessage(),
        ]);

        Storage::disk('local')->delete($this->caminhoTemporario);
    }
}
