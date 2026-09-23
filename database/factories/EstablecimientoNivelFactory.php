<?php

namespace Database\Factories;

use App\Models\Establecimiento;
use App\Models\EstablecimientoNivel;
use App\Models\Sys_Nivel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoNivel>
 */
class EstablecimientoNivelFactory extends Factory
{
    protected $model = EstablecimientoNivel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_id' => Establecimiento::factory(),
            'nivel_id' => Sys_Nivel::factory(),
        ];
    }
}
