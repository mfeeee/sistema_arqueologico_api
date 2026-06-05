<?php

namespace App\Enums;

enum PapelResponsavelBem: string
{
    case PESQUISADOR = 'pesquisador';
    case CURADOR = 'curador';
    case RESPONSAVEL_TECNICO = 'responsavel_tecnico';
    case COORDENADOR = 'coordenador';

    public function label(): string
    {
        return match ($this) {
            self::PESQUISADOR => 'Pesquisador',
            self::CURADOR => 'Curador',
            self::RESPONSAVEL_TECNICO => 'Responsável Técnico',
            self::COORDENADOR => 'Coordenador',
        };
    }
}
