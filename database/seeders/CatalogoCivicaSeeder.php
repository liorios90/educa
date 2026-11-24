<?php

namespace Database\Seeders;

use App\Models\Sys_Asignatura;
use App\Models\Sys_CriterioEvaluacion;
use App\Models\Sys_Destreza;
use App\Models\Sys_Grado;
use App\Models\Sys_IndicadorEvaluacion;
use App\Models\Sys_Subnivel;
use Illuminate\Database\Seeder;

class CatalogoCivicaSeeder extends Seeder
{
    private const ASIGNATURA = 'Cívica y Acompañamiento Integral en el Aula';

    /**
     * Carga las 15 habilidades socioemocionales en cada grado que ofrece Cívica.
     */
    public function run(): void
    {
        /** @var array{grupos: array<string, list<string>>, criterios: list<array{codigo: string, descripcion: string, destrezas: list<array{codigo: string, descripcion: string, imprescindible: bool, logros: array<string, string>}>}>} $catalogo */
        $catalogo = require __DIR__.'/data/civica_acompanamiento.php';

        foreach ($catalogo['grupos'] as $grupo => $subniveles) {
            foreach ($subniveles as $subnivelNombre) {
                $subnivel = Sys_Subnivel::query()->where('nombre', $subnivelNombre)->first();
                $asignatura = $subnivel?->areas()
                    ->where('nombre', 'Acompañamiento socioemocional')
                    ->first()
                    ?->asignaturas()
                    ->where('nombre', self::ASIGNATURA)
                    ->first();

                if (! $subnivel instanceof Sys_Subnivel || ! $asignatura instanceof Sys_Asignatura) {
                    continue;
                }

                foreach ($subnivel->grados as $grado) {
                    $this->cargar($asignatura, $grado, $grupo, $catalogo['criterios']);
                }
            }
        }
    }

    /**
     * @param  list<array{codigo: string, descripcion: string, destrezas: list<array{codigo: string, descripcion: string, imprescindible: bool, logros: array<string, string>}>}>  $criterios
     */
    private function cargar(Sys_Asignatura $asignatura, Sys_Grado $grado, string $grupo, array $criterios): void
    {
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
                        'imprescindible' => $destreza['imprescindible'],
                        'orden' => $ordenDestreza + 1,
                        'activo' => true,
                    ],
                );

                Sys_IndicadorEvaluacion::query()->updateOrCreate(
                    [
                        'destreza_id' => $destrezaModelo->id,
                        'codigo' => 'I.'.$destreza['codigo'],
                    ],
                    [
                        'descripcion' => $destreza['logros'][$grupo],
                        'orden' => 1,
                        'activo' => true,
                    ],
                );
            }
        }
    }
}
