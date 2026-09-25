<?php

namespace Database\Factories;

use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\Sys_Jornada;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoModalidadJornada>
 */
class EstablecimientoModalidadJornadaFactory extends Factory
{
    protected $model = EstablecimientoModalidadJornada::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_modalidad_id' => EstablecimientoModalidad::factory(),
            'jornada_id' => Sys_Jornada::factory(),
        ];
    }
}
