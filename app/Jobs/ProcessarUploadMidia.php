<?php

namespace App\Jobs;

use App\Enums\TipoMidia;
use App\Models\BemMaterial;
use App\Models\Midia;
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
        private readonly TipoMidia $tipoMidia,
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
            $mimeType = Storage::disk('local')->mimeType($this->caminhoTemporario) ?: 'application/octet-stream';

            Storage::disk('public')->put(
                $destino,
                Storage::disk('local')->get($this->caminhoTemporario)
            );

            Midia::create([
                'mediable_type' => BemMaterial::class,
                'mediable_id' => $this->bemMaterialId,
                'storage_disk' => 'public',
                'storage_path' => $destino,
                'mime_type' => $mimeType,
                'tipo' => $this->tipoMidia,
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
