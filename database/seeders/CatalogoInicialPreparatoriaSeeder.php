<?php

namespace Database\Seeders;

use App\Enums\TipoCalificacion;
use App\Models\EstablecimientoAsignatura;
use App\Models\Sys_Asignatura;
use App\Models\Sys_CriterioEvaluacion;
use App\Models\Sys_Destreza;
use App\Models\Sys_IndicadorEvaluacion;
use App\Models\Sys_Subnivel;
use Illuminate\Database\Seeder;

class CatalogoInicialPreparatoriaSeeder extends Seeder
{
    /**
     * Carga criterios, destrezas e indicadores de Inicial y Preparatoria.
     * Se puede volver a ejecutar: actualiza lo existente y no borra calificaciones.
     */
    public function run(): void
    {
        /** @var array<string, array<string, list<array{codigo: string, descripcion: string, destrezas: list<array{codigo: string, descripcion: string, imprescindible?: bool, indicadores: list<array{codigo: string, descripcion: string}>}>}>>> $catalogo */
        $catalogo = require __DIR__.'/data/inicial_preparatoria.php';

        foreach ($catalogo as $subnivelNombre => $ambitos) {
            $subnivel = Sys_Subnivel::query()->where('nombre', $subnivelNombre)->first();
            $grado = $subnivel?->grados()->orderBy('id')->first();

            if ($subnivel === null || $grado === null) {
                continue;
            }

            $subnivel->update([
                'tipo_calificacion' => TipoCalificacion::Cualitativa,
                'califica_por_destrezas' => true,
                'equivale_nota_nacional' => false,
                'usa_ambitos' => true,
            ]);

            EstablecimientoAsignatura::query()
                ->where('grado_id', $grado->id)
                ->update([
                    'tipo_calificacion' => TipoCalificacion::Cualitativa->value,
                    'califica_por_destrezas' => true,
                    'usa_tabla_equivalencias' => false,
                ]);

            foreach ($ambitos as $ambitoNombre => $criterios) {
                $asignatura = Sys_Asignatura::query()
                    ->where('nombre', $ambitoNombre)
                    ->whereHas('area', fn ($query) => $query->where('subnivel_id', $subnivel->id))
                    ->first();

                if ($asignatura === null) {
                    continue;
                }

                foreach ($criterios as $ordenCriterio => $criterio) {
                    $criterioModelo = Sys_CriterioEvaluacion::query()->updateOrCreate(
                        [
                            'asignatura_id' => $asignatura->id,
                            'grado_id' => $grado->id,
                            'codigo' => $criterio['codigo'],
                        ],
                        [
                            'descripcion' => $criterio['descripcion'],
                            'orden' => $ordenCriterio + 1,
                            'activo' => true,
                        ],
                    );

                    foreach ($criterio['destrezas'] as $ordenDestreza => $destreza) {
                        $destrezaModelo = Sys_Destreza::query()->updateOrCreate(
                            [
                                'asignatura_id' => $asignatura->id,
                                'grado_id' => $grado->id,
                                'codigo' => $destreza['codigo'],
                            ],
                            [
                                'criterio_evaluacion_id' => $criterioModelo->id,
                                'descripcion' => $destreza['descripcion'],
                                'imprescindible' => $destreza['imprescindible'] ?? true,
                                'orden' => $ordenDestreza + 1,
                                'activo' => true,
                            ],
                        );

                        foreach ($destreza['indicadores'] as $ordenIndicador => $indicador) {
                            Sys_IndicadorEvaluacion::query()->updateOrCreate(
                                [
                                    'destreza_id' => $destrezaModelo->id,
                                    'codigo' => $indicador['codigo'],
                                ],
                                [
                                    'descripcion' => $indicador['descripcion'],
                                    'orden' => $ordenIndicador + 1,
                                    'activo' => true,
                                ],
                            );
                        }
                    }
                }
            }
        }
    }
}
