<?php

namespace App\Enums;

enum EsquemaCiclo: string
{
    case Quimestres = 'quimestres';
    case Trimestres = 'trimestres';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Quimestres => 'Quimestres',
            self::Trimestres => 'Trimestres',
            self::Otro => 'Otro',
        };
    }

    public function cantidadFija(): ?int
    {
        return match ($this) {
            self::Quimestres => 2,
            self::Trimestres => 3,
            self::Otro => null,
        };
    }

    /**
     * @return list<array{nombre: string, porcentaje: string, porcentaje_insumos: string, tiene_examen: bool, porcentaje_examen: string, tiene_proyecto: bool, porcentaje_proyecto: string}>
     */
    public function plantilla(): array
    {
        $nombres = match ($this) {
            self::Quimestres => ['Primer quimestre', 'Segundo quimestre'],
            self::Trimestres => ['Primer trimestre', 'Segundo trimestre', 'Tercer trimestre'],
            self::Otro => ['Ciclo 1', 'Ciclo 2'],
        };

        $count = count($nombres);
        $base = $count === 0 ? '0.00' : number_format(floor(10000 / $count) / 100, 2, '.', '');
        $ciclos = [];
        $acumulado = 0.0;

        foreach ($nombres as $indice => $nombre) {
            $esUltimo = $indice === $count - 1;
            $porcentaje = $esUltimo
                ? number_format(100 - $acumulado, 2, '.', '')
                : $base;
            $acumulado += (float) $porcentaje;

            $ciclos[] = [
                'nombre' => $nombre,
                'porcentaje' => $porcentaje,
                'porcentaje_insumos' => '70.00',
                'tiene_examen' => true,
                'porcentaje_examen' => '30.00',
                'tiene_proyecto' => false,
                'porcentaje_proyecto' => '0.00',
            ];
        }

        return $ciclos;
    }
}
