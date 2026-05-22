<?php

namespace App\Enums;

enum TipoMencaoArtigo: string
{
    case CITACAO = 'citacao';
    case ESTUDO_APROFUNDADO = 'estudo_aprofundado';
    case REFERENCIA_GEOGRAFICA = 'referencia_geografica';
    case ANALISE_ARTEFATOS = 'analise_artefatos';

    public function label(): string
    {
        return match ($this) {
            self::CITACAO => 'Citação',
            self::ESTUDO_APROFUNDADO => 'Estudo aprofundado',
            self::REFERENCIA_GEOGRAFICA => 'Referência geográfica',
            self::ANALISE_ARTEFATOS => 'Análise de artefatos',
        };
    }
}
