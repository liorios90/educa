<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoGrupo;
use App\Models\EstablecimientoGrupoAlumno;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoGrupoAlumno>
 */
class EstablecimientoGrupoAlumnoFactory extends Factory
{
    protected $model = EstablecimientoGrupoAlumno::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_grupo_id' => EstablecimientoGrupo::factory(),
            'establecimiento_periodo_id' => EstablecimientoPeriodo::factory(),
            'establecimiento_asignatura_id' => EstablecimientoAsignatura::factory(),
            'alumno_id' => Alumno::factory(),
        ];
    }
}
