<?php

namespace App\Enums;

enum TipoCalificacion: string
{
    case Destrezas = 'destrezas';
    case Calificacion = 'calificacion';

    public function label(): string
    {
        return match ($this) {
            self::Destrezas => 'Por destrezas',
            self::Calificacion => 'Por calificación',
        };
    }
}
