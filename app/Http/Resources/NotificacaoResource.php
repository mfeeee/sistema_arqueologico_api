<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificacaoResource extends JsonResource
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
            'titulo' => $this->titulo,
            'corpo' => $this->corpo,
            'tipo' => $this->tipo->value,
            'lida' => $this->lida,
            'data' => $this->created_at->toIso8601String(),
        ];
    }
}
