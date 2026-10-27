<?php

namespace App\Enums;

enum ModoAsistencia: string
{
    case Jornada = 'jornada';
    case Asignatura = 'asignatura';

    public function label(): string
    {
        return match ($this) {
            self::Jornada => 'Una vez al día',
            self::Asignatura => 'Por asignatura',
        };
    }

    public function esJornada(): bool
    {
        return $this === self::Jornada;
    }

    /**
     * Inicial, Preparatoria and Básica Elemental usually have one teacher for the day.
     */
    public static function predeterminado(?string $subnivel): self
    {
        return in_array($subnivel, [
            'Inicial 1',
            'Inicial 2',
            'Preparatoria',
            'Básica Elemental',
        ], true) ? self::Jornada : self::Asignatura;
    }
}
