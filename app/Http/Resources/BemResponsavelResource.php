<?php

namespace App\Http\Resources;

use App\Enums\PerfilUsuario;
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
                // E-mail é dado pessoal: só curador e admin veem (LGPD).
                'email' => $this->when(
                    in_array($request->user()?->perfil, [PerfilUsuario::CURADOR, PerfilUsuario::ADMIN], true),
                    fn () => $this->usuario?->email,
                ),
                'avatar_url' => $this->usuario?->avatar_url,
            ],
        ];
    }
}
