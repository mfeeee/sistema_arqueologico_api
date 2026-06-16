<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ColetaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->id,
            'usuario_id' => $this->usuario_id,
            'data_coleta' => $this->data_coleta?->toIso8601String(),
            'nome_bem' => $this->nome_bem,
            'natureza' => $this->natureza_bem?->value,
            'tipo' => $this->tipo_bem?->value,
            'uf' => $this->uf,
            'versao' => $this->versao,
            'dados_coletados' => $this->dados_coletados,
            'status_sincronizacao' => $this->status_sincronizacao?->value,
            'localizacao' => $this->whenLoaded('localizacao',
                fn () => new LocalizacaoResource($this->localizacao)),
            'artefato_tipos' => ArtefatoTipoResource::collection(
                $this->whenLoaded('artefatoTipos')),
            'midias' => MidiaResource::collection(
                $this->whenLoaded('midias')),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deletado_em' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
