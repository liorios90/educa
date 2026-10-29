<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\EstablecimientoTarea;
use App\Models\EstablecimientoTareaEntrega;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoTareaEntrega>
 */
class EstablecimientoTareaEntregaFactory extends Factory
{
    protected $model = EstablecimientoTareaEntrega::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_tarea_id' => EstablecimientoTarea::factory(),
            'alumno_id' => Alumno::factory(),
            'mensaje' => 'Adjunto el trabajo.',
        ];
    }
}
