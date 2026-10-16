<?php

namespace App\Enums;

enum TipoPrograma: string
{
    case BachilleratoInternacional = 'bachillerato_internacional';

    public function label(): string
    {
        return match ($this) {
            self::BachilleratoInternacional => 'Bachillerato Internacional',
        };
    }
}
