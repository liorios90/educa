<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\EstablecimientoPrograma;
use App\Models\EstablecimientoProgramaAlumno;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoProgramaAlumno>
 */
class EstablecimientoProgramaAlumnoFactory extends Factory
{
    protected $model = EstablecimientoProgramaAlumno::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_programa_id' => EstablecimientoPrograma::factory(),
            'alumno_id' => Alumno::factory(),
        ];
    }
}
