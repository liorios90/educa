<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'Admin';
    case Sistemas = 'Sistemas';
    case Secretaria = 'Secretaria';
    case Padre = 'Padre';
    case Alumno = 'Alumno';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Sistemas => 'Sistemas',
            self::Secretaria => 'Secretaría',
            self::Padre => 'Padre',
            self::Alumno => 'Alumno',
        };
    }

    public function requiresEstablecimiento(): bool
    {
        return match ($this) {
            self::Admin, self::Secretaria, self::Padre, self::Alumno => true,
            self::Sistemas => false,
        };
    }
}
