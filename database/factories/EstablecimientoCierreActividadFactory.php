<?php

namespace Database\Factories;

use App\Enums\TipoCierreEvaluacion;
use App\Models\Empleado;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoCierreActividad;
use App\Models\EstablecimientoPeriodoCiclo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoCierreActividad>
 */
class EstablecimientoCierreActividadFactory extends Factory
{
    protected $model = EstablecimientoCierreActividad::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_aula_id' => EstablecimientoAula::factory(),
            'establecimiento_asignatura_id' => EstablecimientoAsignatura::factory(),
            'establecimiento_periodo_ciclo_id' => EstablecimientoPeriodoCiclo::factory(),
            'tipo' => TipoCierreEvaluacion::Examen,
            'empleado_id' => Empleado::factory(),
            'nombre' => 'Examen del quimestre',
        ];
    }
}
