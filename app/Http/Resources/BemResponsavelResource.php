<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BemResponsavelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'papel' => $this->papel?->value,
            'user' => [
                'id' => $this->usuario?->id,
                'name' => $this->usuario?->name,
                'email' => $this->usuario?->email,
                'avatar_url' => $this->usuario?->avatar_url,
            ],
        ];
    }
}
