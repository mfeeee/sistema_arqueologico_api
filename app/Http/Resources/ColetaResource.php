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
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'localizacao_id' => $this->localizacao_id,
            'data_coleta' => $this->data_coleta,
            'nome_bem' => $this->nome_bem,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'natureza_bem' => $this->natureza_bem,
            'tipo_bem' => $this->tipo_bem,
            'uf' => $this->uf,
            'versao' => (int) $this->versao,
            'dados_coletados' => $this->dados_coletados,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'localizacao' => new LocalizacaoResource($this->whenLoaded('localizacao')),
            'artefato_tipos' => $this->whenLoaded('artefatoTipos'),
            'midias' => $this->whenLoaded('midias'),
        ];
    }
}
