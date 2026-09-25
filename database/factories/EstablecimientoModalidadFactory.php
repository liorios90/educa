<?php

namespace Database\Factories;

use App\Models\Establecimiento;
use App\Models\EstablecimientoModalidad;
use App\Models\Sys_Modalidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoModalidad>
 */
class EstablecimientoModalidadFactory extends Factory
{
    protected $model = EstablecimientoModalidad::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_id' => Establecimiento::factory(),
            'modalidad_id' => Sys_Modalidad::factory(),
        ];
    }
}
