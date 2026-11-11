<?php

namespace Database\Factories;

use App\Enums\CalificacionDestreza;
use App\Models\Alumno;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoDestrezaCalificacion;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\Sys_Destreza;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoDestrezaCalificacion>
 */
class EstablecimientoDestrezaCalificacionFactory extends Factory
{
    protected $model = EstablecimientoDestrezaCalificacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_id' => Establecimiento::factory(),
            'alumno_id' => Alumno::factory(),
            'establecimiento_aula_id' => EstablecimientoAula::factory(),
            'establecimiento_asignatura_id' => EstablecimientoAsignatura::factory(),
            'establecimiento_periodo_ciclo_id' => EstablecimientoPeriodoCiclo::factory(),
            'destreza_id' => Sys_Destreza::factory(),
            'calificacion' => CalificacionDestreza::APlus,
            'observacion' => null,
        ];
    }
}
