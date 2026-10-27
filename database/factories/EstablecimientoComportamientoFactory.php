<?php

namespace Database\Factories;

use App\Enums\UsoEquivalencia;
use App\Models\Alumno;
use App\Models\Equivalencia;
use App\Models\EquivalenciaDetalle;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoComportamiento;
use App\Models\EstablecimientoPeriodo;
use App\Models\EstablecimientoPeriodoCiclo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoComportamiento>
 */
class EstablecimientoComportamientoFactory extends Factory
{
    protected $model = EstablecimientoComportamiento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_id' => Establecimiento::factory(),
            'alumno_id' => Alumno::factory(),
            'establecimiento_aula_id' => EstablecimientoAula::factory(),
            'establecimiento_periodo_ciclo_id' => EstablecimientoPeriodoCiclo::factory(),
            'equivalencia_detalle_id' => function (): int {
                $periodo = EstablecimientoPeriodo::factory()->create();
                $equivalencia = Equivalencia::query()->create([
                    'establecimiento_periodo_id' => $periodo->id,
                    'nombre' => 'Comportamiento',
                    'uso' => UsoEquivalencia::Comportamiento,
                    'activo' => true,
                ]);

                return EquivalenciaDetalle::query()->create([
                    'equivalencia_id' => $equivalencia->id,
                    'nombre' => 'Satisfactorio',
                    'valor' => 'B',
                    'activo' => true,
                    'exige_observacion' => false,
                ])->id;
            },
            'observacion' => null,
        ];
    }
}
