<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoCalificacion;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoCalificacion>
 */
class EstablecimientoCalificacionFactory extends Factory
{
    protected $model = EstablecimientoCalificacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_periodo_id' => EstablecimientoPeriodo::factory(),
            'establecimiento_asignatura_id' => EstablecimientoAsignatura::factory(),
            'alumno_id' => Alumno::factory(),
            'ciclo_orden' => 1,
            'parcial_numero' => 0,
            'nota' => '9.00',
            'establecimiento_grupo_id' => null,
        ];
    }
}
