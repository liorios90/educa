<?php

namespace Database\Factories;

use App\Enums\EstadoAsistencia;
use App\Models\Alumno;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsistencia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoAsistencia>
 */
class EstablecimientoAsistenciaFactory extends Factory
{
    protected $model = EstablecimientoAsistencia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_id' => Establecimiento::factory(),
            'alumno_id' => Alumno::factory(),
            'establecimiento_asignatura_id' => null,
            'establecimiento_aula_id' => null,
            'fecha' => '2026-10-01',
            'estado' => EstadoAsistencia::Presente,
        ];
    }
}
