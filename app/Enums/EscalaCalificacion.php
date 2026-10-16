<?php

namespace App\Enums;

enum EscalaCalificacion: string
{
    case Nacional = 'nacional';
    case BachilleratoInternacional = 'bachillerato_internacional';

    public function label(): string
    {
        return match ($this) {
            self::Nacional => 'Nacional (sobre 10)',
            self::BachilleratoInternacional => 'Bachillerato Internacional (1 a 7)',
        };
    }

    public function minimo(): float
    {
        return match ($this) {
            self::Nacional => 0.0,
            self::BachilleratoInternacional => 1.0,
        };
    }

    public function maximo(): float
    {
        return match ($this) {
            self::Nacional => 10.0,
            self::BachilleratoInternacional => 7.0,
        };
    }

    public function contiene(float $nota): bool
    {
        return $nota >= $this->minimo() && $nota <= $this->maximo();
    }

    public function mensajeRango(): string
    {
        return match ($this) {
            self::Nacional => 'La nota nacional va de 0 a 10.',
            self::BachilleratoInternacional => 'La nota del Bachillerato Internacional va de 1 a 7.',
        };
    }
}
