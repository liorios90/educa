<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoMatricula>
 */
class EstablecimientoMatriculaFactory extends Factory
{
    protected $model = EstablecimientoMatricula::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_id' => Establecimiento::factory(),
            'establecimiento_periodo_id' => EstablecimientoPeriodo::factory(),
            'establecimiento_aula_id' => EstablecimientoAula::factory(),
            'alumno_id' => Alumno::factory(),
            'numero' => 'X'.fake()->unique()->numerify('############'),
            'secuencia' => fake()->unique()->numberBetween(1, 2_000_000),
            'fecha' => now()->toDateString(),
        ];
    }
}
