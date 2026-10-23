<?php

namespace App\Services;

use App\Models\EstablecimientoAulaAsignaturaCiclo;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoPeriodo;
use App\Models\EstablecimientoPeriodoAsignaturaCiclo;
use App\Models\EstablecimientoPeriodoConfiguracionGrado;
use App\Models\HorarioClase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CopiarConfiguracionPeriodo
{
    /**
     * @return array{aulas: int, asignaciones: int, horarios: int}
     */
    public function handle(EstablecimientoPeriodo $origen, EstablecimientoPeriodo $destino): array
    {
        if ((int) $origen->establecimiento_modalidad_jornada_id !== (int) $destino->establecimiento_modalidad_jornada_id) {
            throw new InvalidArgumentException('Los periodos deben pertenecer a la misma oferta académica.');
        }

        $destino->update([
            'esquema_ciclo' => $origen->esquema_ciclo,
            'nombre_esquema' => $origen->nombre_esquema,
            'numero_parciales' => $origen->numero_parciales,
            'modo_ponderacion_ciclos' => $origen->modo_ponderacion_ciclos,
            'porcentaje_promedio_ciclos' => $origen->porcentaje_promedio_ciclos,
            'porcentaje_examen_final' => $origen->porcentaje_examen_final,
            'porcentaje_proyecto_final' => $origen->porcentaje_proyecto_final,
        ]);

        $totales = ['aulas' => 0, 'asignaciones' => 0, 'horarios' => 0];

        foreach ($origen->aulas()->orderBy('id')->get() as $aulaOrigen) {
            $aulaDestino = $aulaOrigen->replicate();
            $aulaDestino->establecimiento_periodo_id = $destino->id;
            $aulaDestino->save();
            $totales['aulas']++;

            foreach (EstablecimientoAulaDocente::query()->where('establecimiento_aula_id', $aulaOrigen->id)->get() as $asignacionOrigen) {
                $asignacionDestino = $asignacionOrigen->replicate();
                $asignacionDestino->establecimiento_aula_id = $aulaDestino->id;
                $asignacionDestino->save();
                $totales['asignaciones']++;
            }

            foreach (HorarioClase::query()->where('establecimiento_aula_id', $aulaOrigen->id)->get() as $horarioOrigen) {
                $horarioDestino = $horarioOrigen->replicate();
                $horarioDestino->establecimiento_aula_id = $aulaDestino->id;
                $horarioDestino->save();
                $totales['horarios']++;
            }

            foreach (EstablecimientoAulaAsignaturaCiclo::query()
                ->where('establecimiento_periodo_id', $origen->id)
                ->where('establecimiento_aula_id', $aulaOrigen->id)
                ->get() as $cicloOrigen) {
                $cicloDestino = $cicloOrigen->replicate();
                $cicloDestino->establecimiento_periodo_id = $destino->id;
                $cicloDestino->establecimiento_aula_id = $aulaDestino->id;
                $cicloDestino->save();
            }
        }

        foreach (EstablecimientoPeriodoAsignaturaCiclo::query()
            ->where('establecimiento_periodo_id', $origen->id)
            ->get() as $cicloAsignaturaOrigen) {
            $cicloAsignaturaDestino = $cicloAsignaturaOrigen->replicate();
            $cicloAsignaturaDestino->establecimiento_periodo_id = $destino->id;
            $cicloAsignaturaDestino->save();
        }

        foreach ($origen->ciclos()->get() as $cicloOrigen) {
            $cicloDestino = $cicloOrigen->replicate();
            $cicloDestino->establecimiento_periodo_id = $destino->id;
            $cicloDestino->save();

            foreach ($cicloOrigen->parciales as $parcialOrigen) {
                $parcialDestino = $parcialOrigen->replicate();
                $parcialDestino->establecimiento_periodo_ciclo_id = $cicloDestino->id;
                $parcialDestino->cerrado_en = null;
                $parcialDestino->save();
            }
        }

        foreach (EstablecimientoPeriodoConfiguracionGrado::query()
            ->where('establecimiento_periodo_id', $origen->id)
            ->with(['grados', 'ciclos'])
            ->get() as $configuracionOrigen) {
            $configuracionDestino = $configuracionOrigen->replicate();
            $configuracionDestino->establecimiento_periodo_id = $destino->id;
            $configuracionDestino->save();

            foreach ($configuracionOrigen->ciclos as $cicloOrigen) {
                $cicloDestino = $cicloOrigen->replicate();
                $cicloDestino->configuracion_grado_id = $configuracionDestino->id;
                $cicloDestino->save();
            }

            foreach ($configuracionOrigen->grados as $grado) {
                DB::table('establecimiento_periodo_configuracion_grado_grados')->insert([
                    'configuracion_grado_id' => $configuracionDestino->id,
                    'grado_id' => $grado->id,
                    'establecimiento_periodo_id' => $destino->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $totales;
    }
}
