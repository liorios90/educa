<?php

namespace Database\Factories;

use App\Models\EstablecimientoPeriodo;
use App\Models\EstablecimientoPeriodoCiclo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoPeriodoCiclo>
 */
class EstablecimientoPeriodoCicloFactory extends Factory
{
    protected $model = EstablecimientoPeriodoCiclo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_periodo_id' => EstablecimientoPeriodo::factory(),
            'orden' => 1,
            'nombre' => 'Primer quimestre',
            'porcentaje' => '50.00',
            'porcentaje_insumos' => '70.00',
            'porcentaje_examen' => '30.00',
            'porcentaje_proyecto' => '0.00',
        ];
    }
}
