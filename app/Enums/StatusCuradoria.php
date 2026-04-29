<?php

namespace App\Enums;

enum StatusCuradoria: string
{
    case PENDENTE = 'pendente';
    case APROVADO = 'aprovado';
    case REJEITADO = 'rejeitado';

    public function label(): string
    {
        return match ($this) {
            self::PENDENTE => 'Pendente',
            self::APROVADO => 'Aprovado',
            self::REJEITADO => 'Rejeitado',
        };
    }
}