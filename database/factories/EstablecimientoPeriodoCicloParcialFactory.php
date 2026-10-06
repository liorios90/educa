<?php

namespace Database\Factories;

use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\EstablecimientoPeriodoCicloParcial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoPeriodoCicloParcial>
 */
class EstablecimientoPeriodoCicloParcialFactory extends Factory
{
    protected $model = EstablecimientoPeriodoCicloParcial::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_periodo_ciclo_id' => EstablecimientoPeriodoCiclo::factory(),
            'numero' => 1,
            'cerrado_en' => null,
        ];
    }
}
