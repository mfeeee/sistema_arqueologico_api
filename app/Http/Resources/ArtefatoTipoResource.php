<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtefatoTipoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Se for a model pivot ColetaArtefatoTipo ou BemArtefatoTipo
        if (isset($this->artefato_tipo_id)) {
            return [
                'id' => $this->artefato_tipo_id,
                'nome' => $this->artefatoTipo?->nome,
                'descricao_nova' => $this->descricao_nova,
                'novo_tipo' => (bool) $this->novo_tipo,
            ];
        }

        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
        ];
    }
}
