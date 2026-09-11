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
}
