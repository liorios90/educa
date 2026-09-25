<?php

namespace Database\Seeders;

use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
use Illuminate\Database\Seeder;

class ModalidadJornadaSeeder extends Seeder
{
    /**
     * Modalidades y jornadas de la educación escolarizada ecuatoriana
     * (LOEI y Reglamento General a la LOEI).
     */
    public function run(): void
    {
        foreach ($this->modalidades() as $modalidad) {
            Sys_Modalidad::query()->updateOrCreate(
                ['nombre' => $modalidad['nombre']],
                ['descripcion' => $modalidad['descripcion']],
            );
        }

        foreach ($this->jornadas() as $jornada) {
            Sys_Jornada::query()->updateOrCreate(
                ['nombre' => $jornada['nombre']],
                ['descripcion' => $jornada['descripcion']],
            );
        }
    }

    /**
     * @return list<array{nombre: string, descripcion: string}>
     */
    private function modalidades(): array
    {
        return [
            [
                'nombre' => 'Presencial',
                'descripcion' => 'Proceso educativo con asistencia física al establecimiento, según la LOEI y su reglamento.',
            ],
            [
                'nombre' => 'Semipresencial',
                'descripcion' => 'Combinación de asistencia presencial y trabajo autónomo o mediado, según la normativa vigente.',
            ],
            [
                'nombre' => 'Virtual',
                'descripcion' => 'Educación a distancia mediada por tecnologías, equivalente a la modalidad a distancia de la LOEI.',
            ],
        ];
    }

    /**
     * @return list<array{nombre: string, descripcion: string}>
     */
    private function jornadas(): array
    {
        return [
            [
                'nombre' => 'Matutina',
                'descripcion' => 'Jornada de la mañana.',
            ],
            [
                'nombre' => 'Vespertina',
                'descripcion' => 'Jornada de la tarde.',
            ],
            [
                'nombre' => 'Nocturna',
                'descripcion' => 'Jornada de la noche.',
            ],
            [
                'nombre' => 'Otro',
                'descripcion' => 'Jornada intensiva, de fines de semana u otra jornada autorizada.',
            ],
        ];
    }
}
