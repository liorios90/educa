<?php

namespace App\Enums;

enum ModoLibretaArea: string
{
    case Asignaturas = 'asignaturas';
    case AreaPromedio = 'area_promedio';
    case AreaYAsignaturas = 'area_y_asignaturas';

    public function label(): string
    {
        return match ($this) {
            self::Asignaturas => 'Solo asignaturas',
            self::AreaPromedio => 'Solo el área (promedio)',
            self::AreaYAsignaturas => 'Área y asignaturas',
        };
    }
}
