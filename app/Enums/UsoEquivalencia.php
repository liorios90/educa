<?php

namespace App\Enums;

enum UsoEquivalencia: string
{
    case Asignatura = 'asignatura';
    case Comportamiento = 'comportamiento';

    public function label(): string
    {
        return match ($this) {
            self::Asignatura => 'Asignatura',
            self::Comportamiento => 'Comportamiento',
        };
    }
}
