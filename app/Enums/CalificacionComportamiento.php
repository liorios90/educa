<?php

namespace App\Enums;

enum CalificacionComportamiento: string
{
    case Siempre = 'S';
    case Frecuentemente = 'F';
    case Ocasionalmente = 'O';
    case Nunca = 'N';

    public function label(): string
    {
        return match ($this) {
            self::Siempre => 'Siempre',
            self::Frecuentemente => 'Frecuentemente',
            self::Ocasionalmente => 'Ocasionalmente',
            self::Nunca => 'Nunca',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::Siempre => 'Demuestra la habilidad en todas las situaciones observadas.',
            self::Frecuentemente => 'Demuestra la habilidad en la mayoría de las situaciones observadas.',
            self::Ocasionalmente => 'Demuestra la habilidad solo en algunas situaciones observadas.',
            self::Nunca => 'No demuestra la habilidad en las situaciones observadas.',
        };
    }

    public function jerarquia(): int
    {
        return match ($this) {
            self::Siempre => 4,
            self::Frecuentemente => 3,
            self::Ocasionalmente => 2,
            self::Nunca => 1,
        };
    }

    public function exigeObservacion(): bool
    {
        return match ($this) {
            self::Ocasionalmente, self::Nunca => true,
            default => false,
        };
    }
}
