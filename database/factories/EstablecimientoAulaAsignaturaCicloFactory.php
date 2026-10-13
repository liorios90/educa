<?php

namespace Database\Factories;

use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaAsignaturaCiclo;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoAulaAsignaturaCiclo>
 */
class EstablecimientoAulaAsignaturaCicloFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_periodo_id' => EstablecimientoPeriodo::factory(),
            'establecimiento_aula_id' => EstablecimientoAula::factory(),
            'establecimiento_asignatura_id' => EstablecimientoAsignatura::factory(),
            'ciclo_orden' => fake()->numberBetween(1, 3),
            'porcentaje_insumos' => '70.00',
            'porcentaje_examen' => '20.00',
            'porcentaje_proyecto' => '10.00',
        ];
    }
}
