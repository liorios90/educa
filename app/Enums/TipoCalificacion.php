<?php

namespace App\Enums;

enum TipoCalificacion: string
{
    case Cualitativa = 'cualitativa';
    case Cuantitativa = 'cuantitativa';

    public function label(): string
    {
        return match ($this) {
            self::Cualitativa => 'Cualitativa',
            self::Cuantitativa => 'Cuantitativa',
        };
    }
}
