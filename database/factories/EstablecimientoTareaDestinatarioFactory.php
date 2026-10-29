<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\EstablecimientoTarea;
use App\Models\EstablecimientoTareaDestinatario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoTareaDestinatario>
 */
class EstablecimientoTareaDestinatarioFactory extends Factory
{
    protected $model = EstablecimientoTareaDestinatario::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_tarea_id' => EstablecimientoTarea::factory(),
            'alumno_id' => Alumno::factory(),
            'nota' => null,
            'observacion' => null,
            'calificada_at' => null,
        ];
    }
}
