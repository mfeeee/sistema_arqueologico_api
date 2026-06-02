<?php

namespace App\Enums;

enum ArtefatoBem: string
{
    case FAIANCA = 'faianca';
    case CERAMICA = 'ceramica';
    case LITICO = 'litico';
    case MADEIRA = 'madeira';
    case MALACOLOGICO = 'malacologico';
    case SEMENTE = 'semente';
    case OSSOS_FAUNISTICOS = 'ossosFaunisticos';
    case PLASTICO = 'plastico';
    case GRES = 'gres';
    case CARVAO = 'carvao';
    case FAIANCA_FINA = 'faiancaFina';
    case PORCELANA = 'porcelana';
    case TEXTIL = 'textil';
    case FIBRA_VEGETAL = 'fibraVegetal';
    case VITREO = 'vitreo';
    case BORRACHA = 'borracha';
    case SEDIMENTO = 'sedimento';
    case CERAMICA_VIDRADA = 'ceramicaVidrada';
    case METALICO = 'metalico';
    case OSSOS_HUMANOS = 'ossosHumanos';
    case OUTROS = 'outros';

    public function label(): string
    {
        return match ($this) {
            self::FAIANCA => 'Faiança',
            self::CERAMICA => 'Cerâmica',
            self::LITICO => 'Lítico',
            self::MADEIRA => 'Madeira',
            self::MALACOLOGICO => 'Malacológico',
            self::SEMENTE => 'Semente',
            self::OSSOS_FAUNISTICOS => 'Ossos faunísticos',
            self::PLASTICO => 'Plástico',
            self::GRES => 'Grés',
            self::CARVAO => 'Carvão',
            self::FAIANCA_FINA => 'Faiança fina',
            self::PORCELANA => 'Porcelana',
            self::TEXTIL => 'Têxtil',
            self::FIBRA_VEGETAL => 'Fibra Vegetal',
            self::VITREO => 'Vítreo',
            self::BORRACHA => 'Borracha',
            self::SEDIMENTO => 'Sedimento',
            self::CERAMICA_VIDRADA => 'Cerâmica vidrada',
            self::METALICO => 'Metálico',
            self::OSSOS_HUMANOS => 'Ossos humanos',
            self::OUTROS => 'Outros',
        };
    }
}
