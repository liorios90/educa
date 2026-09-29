<?php

namespace Database\Factories;

use App\Models\Establecimiento;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoPeriodo>
 */
class EstablecimientoPeriodoFactory extends Factory
{
    protected $model = EstablecimientoPeriodo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('2026-01-01', '2026-06-01');
        $fin = (clone $inicio)->modify('+9 months');

        return [
            'establecimiento_id' => Establecimiento::factory(),
            'nombre' => 'Periodo '.fake()->unique()->numerify('####'),
            'fecha_inicio' => $inicio->format('Y-m-d'),
            'fecha_fin' => $fin->format('Y-m-d'),
            'activo' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (EstablecimientoPeriodo $periodo): void {
            if ($periodo->establecimiento_modalidad_jornada_id !== null) {
                return;
            }

            $oferta = $periodo->establecimiento_id
                ? EstablecimientoModalidadJornada::factory()->create([
                    'establecimiento_modalidad_id' => EstablecimientoModalidad::factory()->create([
                        'establecimiento_id' => $periodo->establecimiento_id,
                    ]),
                ])
                : EstablecimientoModalidadJornada::factory()->create();

            $periodo->establecimiento_modalidad_jornada_id = $oferta->id;
            $periodo->establecimiento_id = $oferta->establecimientoModalidad->establecimiento_id;
        });
    }

    public function activo(): static
    {
        return $this->state(['activo' => true]);
    }
}
