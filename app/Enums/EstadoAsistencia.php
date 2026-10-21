<?php

namespace App\Enums;

enum EstadoAsistencia: string
{
    case Presente = 'presente';
    case Falta = 'falta';
    case Atraso = 'atraso';
    case Justificada = 'justificada';

    public function label(): string
    {
        return match ($this) {
            self::Presente => 'Presente',
            self::Falta => 'Falta',
            self::Atraso => 'Atraso',
            self::Justificada => 'Falta justificada',
        };
    }
}
