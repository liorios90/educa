<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoIndicadorObservacion;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\Sys_IndicadorEvaluacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoIndicadorObservacion>
 */
class EstablecimientoIndicadorObservacionFactory extends Factory
{
    protected $model = EstablecimientoIndicadorObservacion::class;

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
            'indicador_evaluacion_id' => Sys_IndicadorEvaluacion::factory(),
        ];
    }
}
