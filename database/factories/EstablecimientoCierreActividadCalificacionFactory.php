<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\EstablecimientoCierreActividad;
use App\Models\EstablecimientoCierreActividadCalificacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoCierreActividadCalificacion>
 */
class EstablecimientoCierreActividadCalificacionFactory extends Factory
{
    protected $model = EstablecimientoCierreActividadCalificacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_cierre_actividad_id' => EstablecimientoCierreActividad::factory(),
            'alumno_id' => Alumno::factory(),
            'nota' => '8.50',
        ];
    }
}
