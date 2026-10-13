<?php

namespace App\Services;

use App\Enums\EsquemaCiclo;
use App\Models\EstablecimientoPeriodo;
use App\Models\EstablecimientoPeriodoAsignaturaCiclo;
use Illuminate\Support\Facades\DB;
use LogicException;

class SyncPeriodoEvaluacion
{
    /**
     * @param  list<array{nombre: string, porcentaje: string, porcentaje_insumos: string, porcentaje_examen: string, porcentaje_proyecto: string}>  $ciclos
     * @param  list<array{establecimiento_asignatura_id: int, ciclos: list<array{orden: int, porcentaje: string}>}>|null  $ponderacionesGrado
     */
    public function handle(
        EstablecimientoPeriodo $periodo,
        EsquemaCiclo $esquema,
        string $nombreEsquema,
        int $parciales,
        string $examenFinal,
        string $proyectoFinal,
        array $ciclos,
        ?array $ponderacionesGrado = null,
    ): void {
        DB::transaction(function () use ($periodo, $esquema, $nombreEsquema, $parciales, $examenFinal, $proyectoFinal, $ciclos, $ponderacionesGrado): void {
            $periodo = EstablecimientoPeriodo::query()
                ->whereKey($periodo->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($periodo->estaAbierto()) {
                throw new LogicException('No se puede modificar la evaluación de un periodo aperturado.');
            }

            $periodo->update([
                'esquema_ciclo' => $esquema,
                'nombre_esquema' => $nombreEsquema,
                'numero_parciales' => $parciales,
                'porcentaje_examen_final' => $examenFinal,
                'porcentaje_proyecto_final' => $proyectoFinal,
            ]);

            $periodo->ciclos()->delete();

            foreach (array_values($ciclos) as $indice => $ciclo) {
                $periodo->ciclos()->create([
                    'orden' => $indice + 1,
                    'nombre' => $ciclo['nombre'],
                    'porcentaje' => $ciclo['porcentaje'],
                    'porcentaje_insumos' => $ciclo['porcentaje_insumos'],
                    'porcentaje_examen' => $ciclo['porcentaje_examen'],
                    'porcentaje_proyecto' => $ciclo['porcentaje_proyecto'],
                ]);
            }

            if ($ponderacionesGrado !== null) {
                EstablecimientoPeriodoAsignaturaCiclo::query()
                    ->where('establecimiento_periodo_id', $periodo->id)
                    ->delete();

                foreach ($ponderacionesGrado as $ponderacionAsignatura) {
                    foreach ($ponderacionAsignatura['ciclos'] as $ciclo) {
                        EstablecimientoPeriodoAsignaturaCiclo::query()->create([
                            'establecimiento_periodo_id' => $periodo->id,
                            'establecimiento_asignatura_id' => $ponderacionAsignatura['establecimiento_asignatura_id'],
                            'ciclo_orden' => $ciclo['orden'],
                            'porcentaje' => $ciclo['porcentaje'],
                        ]);
                    }
                }
            }
        });
    }
}
