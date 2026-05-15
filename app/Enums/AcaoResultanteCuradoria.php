<?php

namespace App\Enums;

enum AcaoResultanteCuradoria: string
{
    case CRIAR_SITIO = 'criarSitio';
    case ATUALIZAR_SITIO = 'atualizarSitio';
    case REJEITAR = 'rejeitar';

    public function label(): string
    {
        return match ($this) {
            self::CRIAR_SITIO => 'Criar sítio',
            self::ATUALIZAR_SITIO => 'Atualizar sítio',
            self::REJEITAR => 'Rejeitar',
        };
    }
}
