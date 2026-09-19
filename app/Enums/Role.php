<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'Admin';
    case Sistemas = 'Sistemas';
    case Secretaria = 'Secretaria';
    case Padre = 'Padre';
    case Alumno = 'Alumno';
    case Empleado = 'Empleado';
    case Docente = 'Docente';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Sistemas => 'Sistemas',
            self::Secretaria => 'Secretaría',
            self::Padre => 'Padre',
            self::Alumno => 'Alumno',
            self::Empleado => 'Empleado',
            self::Docente => 'Docente',
        };
    }

    public function requiresEstablecimiento(): bool
    {
        return match ($this) {
            self::Admin, self::Secretaria, self::Padre, self::Alumno, self::Empleado, self::Docente => true,
            self::Sistemas => false,
        };
    }

    public function isAssignableToEmpleado(): bool
    {
        return match ($this) {
            self::Admin, self::Sistemas, self::Secretaria, self::Empleado, self::Docente => true,
            self::Padre, self::Alumno => false,
        };
    }

    /**
     * @return list<self>
     */
    public static function assignableToEmpleado(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $role): bool => $role->isAssignableToEmpleado(),
        ));
    }
}
