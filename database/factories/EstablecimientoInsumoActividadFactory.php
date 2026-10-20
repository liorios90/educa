<?php

namespace Database\Factories;

use App\Models\Empleado;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoInsumoActividad;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\TipoInsumo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoInsumoActividad>
 */
class EstablecimientoInsumoActividadFactory extends Factory
{
    protected $model = EstablecimientoInsumoActividad::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tipo = TipoInsumo::query()->create([
            'nombre' => 'Evaluación',
            'tipo_calificacion' => 'cuantitativa',
            'activo' => true,
        ]);

        return [
            'establecimiento_aula_id' => EstablecimientoAula::factory(),
            'establecimiento_asignatura_id' => EstablecimientoAsignatura::factory(),
            'establecimiento_periodo_ciclo_id' => EstablecimientoPeriodoCiclo::factory(),
            'parcial_numero' => 1,
            'tipo_insumo_detalle_id' => $tipo->detalles()->create([
                'nombre' => 'Arte',
                'porcentaje' => '50.00',
                'activo' => true,
            ])->id,
            'empleado_id' => Empleado::factory(),
            'nombre' => 'Ensayo de arte',
        ];
    }
}
