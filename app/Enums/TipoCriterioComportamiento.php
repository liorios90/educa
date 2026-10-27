<?php

namespace App\Enums;

enum TipoCriterioComportamiento: string
{
    case Cognitiva = 'cognitiva';
    case Social = 'social';
    case Emocional = 'emocional';

    public function label(): string
    {
        return match ($this) {
            self::Cognitiva => 'Cognitiva',
            self::Social => 'Social',
            self::Emocional => 'Emocional',
        };
    }

    public function grupo(): string
    {
        return match ($this) {
            self::Cognitiva => 'Habilidades cognitivas',
            self::Social => 'Habilidades sociales',
            self::Emocional => 'Habilidades emocionales',
        };
    }
}
