<?php

namespace App\Enums;

enum TipoBem: string
{
    case ACERVO_OU_COLECAO = 'acervoOuColecao';
    case BEM_OU_CONJUNTO = 'bemOuConjunto';
    case COLECAO = 'colecao';
    case SITIO = 'sitio';

    public function label(): string
    {
        return match ($this) {
            self::ACERVO_OU_COLECAO => 'Acervo ou coleção',
            self::BEM_OU_CONJUNTO => 'Bem ou conjunto',
            self::COLECAO => 'Coleção',
            self::SITIO => 'Sítio',
        };
    }
}