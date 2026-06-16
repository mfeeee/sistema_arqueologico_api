<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocalizacaoResource extends JsonResource
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
            'cep' => $this->cep,
            'logradouro' => $this->logradouro,
            'municipio' => $this->municipio,
            'uf' => $this->uf,
            'lat' => $this->lat,
            'lng' => $this->lng,
        ];
    }
}
