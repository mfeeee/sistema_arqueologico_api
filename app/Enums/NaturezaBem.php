<?php

namespace App\Enums;

enum NaturezaBem: string
{
    case ARQUEOLOGICO = 'bemArqueologico';
    case PALEONTOLOGICO = 'bemPaleontologico';

    public function label(): string
    {
        return match ($this) {
            self::ARQUEOLOGICO => 'Arqueológico',
            self::PALEONTOLOGICO => 'Paleontológico',
        };
    }
}
