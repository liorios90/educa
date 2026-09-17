<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'Admin';
    case Sistemas = 'Sistemas';
    case Secretaria = 'Secretaria';
    case Padre = 'Padre';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Sistemas => 'Sistemas',
            self::Secretaria => 'Secretaría',
            self::Padre => 'Padre',
        };
    }

    public function requiresEstablecimiento(): bool
    {
        return match ($this) {
            self::Admin, self::Secretaria, self::Padre => true,
            self::Sistemas => false,
        };
    }
}
