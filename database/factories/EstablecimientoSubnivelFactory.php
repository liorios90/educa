<?php

namespace Database\Factories;

use App\Models\Establecimiento;
use App\Models\EstablecimientoSubnivel;
use App\Models\Sys_Subnivel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoSubnivel>
 */
class EstablecimientoSubnivelFactory extends Factory
{
    protected $model = EstablecimientoSubnivel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subnivel = Sys_Subnivel::factory()->create();

        return [
            'establecimiento_id' => Establecimiento::factory(),
            'nivel_id' => $subnivel->nivel_id,
            'subnivel_id' => $subnivel->id,
        ];
    }
}
