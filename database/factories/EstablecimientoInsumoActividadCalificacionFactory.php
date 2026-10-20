<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\EstablecimientoInsumoActividad;
use App\Models\EstablecimientoInsumoActividadCalificacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoInsumoActividadCalificacion>
 */
class EstablecimientoInsumoActividadCalificacionFactory extends Factory
{
    protected $model = EstablecimientoInsumoActividadCalificacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_insumo_actividad_id' => EstablecimientoInsumoActividad::factory(),
            'alumno_id' => Alumno::factory(),
            'nota' => '8.50',
        ];
    }
}
