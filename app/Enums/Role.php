<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'Admin';
    case Sistemas = 'Sistemas';
    case Secretaria = 'Secretaria';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Sistemas => 'Sistemas',
            self::Secretaria => 'Secretaría',
        };
    }

    public function requiresEstablecimiento(): bool
    {
        return match ($this) {
            self::Admin, self::Secretaria => true,
            self::Sistemas => false,
        };
    }
}
