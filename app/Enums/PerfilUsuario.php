<?php

namespace App\Enums;

enum PerfilUsuario: string
{
    case COLETOR = 'coletor';
    case CURADOR = 'curador';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::COLETOR => 'Coletor',
            self::CURADOR => 'Curador',
            self::ADMIN => 'Administrador',
        };
    }
}