<?php

namespace App\Enums;

enum TipoMidia: string
{
    case IMAGEM = 'imagem';
    case VIDEO = 'video';
    case TESE = 'tese';
    case ARTIGO = 'artigo';

    public function label(): string
    {
        return match ($this) {
            self::IMAGEM => 'Imagem',
            self::VIDEO => 'Vídeo',
            self::TESE => 'Tese',
            self::ARTIGO => 'Artigo',
        };
    }
}
