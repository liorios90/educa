<?php

namespace App\Enums;

enum TipoAsignatura: string
{
    case TroncoComun = 'tronco_comun';
    case Pedagogica = 'pedagogica';
    case Optativa = 'optativa';
    case Extra = 'extra';

    public function label(): string
    {
        return match ($this) {
            self::TroncoComun => 'Tronco común',
            self::Pedagogica => 'Pedagógica',
            self::Optativa => 'Optativa',
            self::Extra => 'Extra',
        };
    }
}
