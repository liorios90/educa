<?php

namespace Database\Factories;

use App\Models\Establecimiento;
use App\Models\EstablecimientoGrado;
use App\Models\Sys_Grado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoGrado>
 */
class EstablecimientoGradoFactory extends Factory
{
    protected $model = EstablecimientoGrado::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grado = Sys_Grado::factory()->create();

        return [
            'establecimiento_id' => Establecimiento::factory(),
            'subnivel_id' => $grado->subnivel_id,
            'grado_id' => $grado->id,
        ];
    }
}
