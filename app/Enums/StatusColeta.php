<?php

namespace App\Enums;

enum StatusColeta: string
{
    case PENDENTE = 'pendente';
    case SINCRONIZADO = 'sincronizado';
    case CONFLITO = 'conflito';

    public function label(): string
    {
        return match ($this) {
            self::PENDENTE => 'Pendente',
            self::SINCRONIZADO => 'Sincronizado',
            self::CONFLITO => 'Conflito',
        };
    }
}