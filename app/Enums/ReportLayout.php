<?php

namespace App\Enums;

enum ReportLayout: string
{
    case Canvas = 'canvas';
    case Table = 'table';

    public function label(): string
    {
        return match ($this) {
            self::Canvas => 'Hoja libre',
            self::Table => 'Tabla de resultados',
        };
    }
}
