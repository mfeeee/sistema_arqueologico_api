<?php

namespace App\Enums;

enum ArtefatoBem: string
{
    case FAIANCA = 'faiança';
    case CERAMICA = 'ceramica';
    case LITICO = 'litico';
    case METAL = 'metal';
    case OSSO = 'osso';
    case VIDRO = 'vidro';
    case MADEIRA = 'madeira';
    case COURO = 'couro';

    public function label(): string
    {
        return match ($this) {
            self::FAIANCA => 'Faiança',
            self::CERAMICA => 'Cerâmica',
            self::LITICO => 'Lítico',
            self::METAL => 'Metal',
            self::OSSO => 'Osso',
            self::VIDRO => 'Vidro',
            self::MADEIRA => 'Madeira',
            self::COURO => 'Couro',
        };
    }
}