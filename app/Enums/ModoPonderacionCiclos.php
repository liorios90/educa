<?php

namespace App\Enums;

enum ModoPonderacionCiclos: string
{
    case Individual = 'individual';
    case Promedio = 'promedio';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Porcentaje de cada ciclo',
            self::Promedio => 'Promedio de los ciclos',
        };
    }

    public function promediaLosCiclos(): bool
    {
        return $this === self::Promedio;
    }
}
