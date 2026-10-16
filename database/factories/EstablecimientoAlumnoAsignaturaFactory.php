<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\EstablecimientoAlumnoAsignatura;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoAlumnoAsignatura>
 */
class EstablecimientoAlumnoAsignaturaFactory extends Factory
{
    protected $model = EstablecimientoAlumnoAsignatura::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_periodo_id' => EstablecimientoPeriodo::factory(),
            'establecimiento_asignatura_id' => EstablecimientoAsignatura::factory(),
            'alumno_id' => Alumno::factory(),
        ];
    }
}
