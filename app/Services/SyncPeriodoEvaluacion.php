<?php

namespace App\Services;

use App\Enums\EsquemaCiclo;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Support\Facades\DB;

class SyncPeriodoEvaluacion
{
    /**
     * @param  list<array{nombre: string, porcentaje: string, porcentaje_insumos: string, porcentaje_examen: string, porcentaje_proyecto: string}>  $ciclos
     */
    public function handle(
        EstablecimientoPeriodo $periodo,
        EsquemaCiclo $esquema,
        int $parciales,
        string $examenFinal,
        string $proyectoFinal,
        array $ciclos,
    ): void {
        DB::transaction(function () use ($periodo, $esquema, $parciales, $examenFinal, $proyectoFinal, $ciclos): void {
            $periodo->update([
                'esquema_ciclo' => $esquema,
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
        });
    }
}
