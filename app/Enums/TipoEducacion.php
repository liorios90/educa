<?php

namespace App\Enums;

enum TipoEducacion: string
{
    case Ordinaria = 'ordinaria';
    case Intensiva = 'intensiva';

    public function label(): string
    {
        return match ($this) {
            self::Ordinaria => 'Ordinaria',
            self::Intensiva => 'Bachillerato intensivo (adultos)',
        };
    }
}
