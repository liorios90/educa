<?php

namespace App\Enums;

enum ModoDecimal: string
{
    case Aproximar = 'aproximar';
    case Truncar = 'truncar';

    public function label(): string
    {
        return match ($this) {
            self::Aproximar => 'Aproximar al inmediato superior',
            self::Truncar => 'Truncar',
        };
    }
}
