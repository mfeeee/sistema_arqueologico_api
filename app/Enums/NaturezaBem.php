<?php

namespace App\Enums;

enum NaturezaBem: string
{
    case ARQUEOLOGICO = 'arqueologico';
    case PALEONTOLOGICO = 'paleontologico';

    public function label(): string
    {
        return match ($this) {
            self::ARQUEOLOGICO => 'Arqueológico',
            self::PALEONTOLOGICO => 'Paleontológico',
        };
    }
}