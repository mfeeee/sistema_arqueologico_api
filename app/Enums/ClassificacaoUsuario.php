<?php

namespace App\Enums;

enum ClassificacaoUsuario: string
{
    case ESTUDANTE = 'estudante';
    case PROFESSOR = 'professor';
    case ARQUEOLOGO = 'arqueologo';

    public function label(): string
    {
        return match ($this) {
            self::ESTUDANTE => 'Estudante',
            self::PROFESSOR => 'Professor',
            self::ARQUEOLOGO => 'Arqueólogo',
        };
    }
}
