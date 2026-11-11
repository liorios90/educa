<?php

namespace App\Services;

use App\Auth\ContextoDestreza;
use App\Enums\CalificacionDestreza;
use App\Models\Alumno;
use App\Models\EstablecimientoDestrezaCalificacion;
use App\Models\EstablecimientoIndicadorCalificacion;
use App\Models\EstablecimientoIndicadorObservacion;
use App\Models\Sys_Destreza;
use App\Models\Sys_IndicadorEvaluacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Evaluación cualitativa del currículo.
 *
 * En Inicial y Preparatoria se califica cada destreza del ámbito, sin promedio.
 * En Básica Elemental se califica cada indicador y esa escala equivale a una nota sobre 10.
 */
class RegistroDestrezas
{
    /**
     * @param  Collection<int, Alumno>  $alumnos
     * @return array{
     *     equivale: bool,
     *     usa_ambitos: bool,
     *     escala: list<array{codigo: string, etiqueta: string, descripcion: string}>,
     *     destrezas: list<array{id: int, codigo: string, descripcion: string, imprescindible: bool, criterio: ?array{codigo: string, descripcion: string}, indicadores: list<array{id: int, codigo: string, descripcion: string}>}>,
     *     alumnos: list<array{id: int, nombre: string, indicadores: array<int, array{calificacion: ?string, observacion: ?string}>, calificaciones: array<int, array{calificacion: ?string, observacion: ?string, indicadores: list<int>}>}>
     * }
     */
    public function presentar(ContextoDestreza $contexto, Collection $alumnos): array
    {
        $contexto->aula->loadMissing('establecimientoGrado.grado.subnivel');
        $usaAmbitos = $this->usaAmbitos($contexto);
        $destrezas = $this->catalogo($contexto);
        $destrezaFilas = $destrezas->map(fn (Sys_Destreza $destreza): array => [
            'id' => (int) $destreza->id,
            'codigo' => $destreza->codigo,
            'descripcion' => $destreza->descripcion,
            'imprescindible' => (bool) $destreza->imprescindible,
            'criterio' => $destreza->criterio === null ? null : [
                'codigo' => $destreza->criterio->codigo,
                'descripcion' => $destreza->criterio->descripcion,
            ],
            'indicadores' => $destreza->indicadores->map(fn (Sys_IndicadorEvaluacion $indicador): array => [
                'id' => (int) $indicador->id,
                'codigo' => $indicador->codigo,
                'descripcion' => $indicador->descripcion,
            ])->values()->all(),
        ])->values()->all();

        $filas = $usaAmbitos
            ? $this->filasPorDestreza($contexto, $alumnos, $destrezas)
            : $this->filasPorIndicador($contexto, $alumnos, $destrezas);

        return [
            'equivale' => (bool) $contexto->aula->establecimientoGrado?->grado?->subnivel?->equivale_nota_nacional,
            'usa_ambitos' => $usaAmbitos,
            'escala' => $usaAmbitos ? CalificacionDestreza::opcionesAmbito() : CalificacionDestreza::opciones(),
            'destrezas' => $destrezaFilas,
            'alumnos' => $filas,
        ];
    }

    /**
     * @param  array<int, array<int, array{calificacion: string, observacion: ?string, indicadores?: list<int>}>>  $filas
     */
    public function guardar(ContextoDestreza $contexto, array $filas): void
    {
        if ($this->usaAmbitos($contexto)) {
            $this->guardarDestrezas($contexto, $filas);

            return;
        }

        $this->guardarIndicadores($contexto, $filas);
    }

    /**
     * @return Collection<int, int>
     */
    public function indicadorIds(ContextoDestreza $contexto): Collection
    {
        $catalogo = $this->catalogo($contexto);

        if ($this->usaAmbitos($contexto)) {
            return $catalogo
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->sort()
                ->values();
        }

        return $catalogo
            ->flatMap(fn (Sys_Destreza $destreza) => $destreza->indicadores->pluck('id'))
            ->map(fn (mixed $id): int => (int) $id)
            ->sort()
            ->values();
    }

    /**
     * @return Collection<int, list<int>>
     */
    public function indicadoresPorDestreza(ContextoDestreza $contexto): Collection
    {
        return $this->catalogo($contexto)->mapWithKeys(
            fn (Sys_Destreza $destreza): array => [
                (int) $destreza->id => $destreza->indicadores
                    ->pluck('id')
                    ->map(fn (mixed $id): int => (int) $id)
                    ->all(),
            ],
        );
    }

    /**
     * @param  Collection<int, Alumno>  $alumnos
     * @param  Collection<int, Sys_Destreza>  $destrezas
     * @return list<array{id: int, nombre: string, indicadores: array<int, array{calificacion: ?string, observacion: ?string}>, calificaciones: array<int, array{calificacion: ?string, observacion: ?string, indicadores: list<int>}>}>
     */
    private function filasPorDestreza(ContextoDestreza $contexto, Collection $alumnos, Collection $destrezas): array
    {
        $registros = EstablecimientoDestrezaCalificacion::query()
            ->where('establecimiento_aula_id', $contexto->aula->id)
            ->where('establecimiento_asignatura_id', $contexto->asignatura->id)
            ->where('establecimiento_periodo_ciclo_id', $contexto->ciclo->id)
            ->get()
            ->keyBy(fn (EstablecimientoDestrezaCalificacion $registro): string => $registro->alumno_id.'-'.$registro->destreza_id);
        $observaciones = EstablecimientoIndicadorObservacion::query()
            ->where('establecimiento_aula_id', $contexto->aula->id)
            ->where('establecimiento_asignatura_id', $contexto->asignatura->id)
            ->where('establecimiento_periodo_ciclo_id', $contexto->ciclo->id)
            ->get()
            ->groupBy(fn (EstablecimientoIndicadorObservacion $observacion): int => (int) $observacion->alumno_id);
        $filas = [];

        foreach ($alumnos as $alumno) {
            $celdas = [];
            $marcadosDelAlumno = $observaciones
                ->get((int) $alumno->id, collect())
                ->map(fn (EstablecimientoIndicadorObservacion $observacion): int => (int) $observacion->indicador_evaluacion_id);

            foreach ($destrezas as $destreza) {
                $registro = $registros->get($alumno->id.'-'.$destreza->id);
                $ids = $destreza->indicadores->pluck('id')->map(fn (mixed $id): int => (int) $id);
                $celdas[(int) $destreza->id] = [
                    'calificacion' => $registro?->calificacion?->value,
                    'observacion' => $registro?->observacion,
                    'indicadores' => $marcadosDelAlumno->intersect($ids)->values()->all(),
                ];
            }

            $filas[] = [
                'id' => (int) $alumno->id,
                'nombre' => trim(($alumno->persona->apellidos ?? '').' '.($alumno->persona->nombres ?? '')),
                'indicadores' => [],
                'calificaciones' => $celdas,
            ];
        }

        return $filas;
    }

    /**
     * @param  Collection<int, Alumno>  $alumnos
     * @param  Collection<int, Sys_Destreza>  $destrezas
     * @return list<array{id: int, nombre: string, indicadores: array<int, array{calificacion: ?string, observacion: ?string}>, calificaciones: array<int, array{calificacion: ?string, observacion: ?string}>}>
     */
    private function filasPorIndicador(ContextoDestreza $contexto, Collection $alumnos, Collection $destrezas): array
    {
        $registros = EstablecimientoIndicadorCalificacion::query()
            ->where('establecimiento_aula_id', $contexto->aula->id)
            ->where('establecimiento_asignatura_id', $contexto->asignatura->id)
            ->where('establecimiento_periodo_ciclo_id', $contexto->ciclo->id)
            ->get()
            ->keyBy(fn (EstablecimientoIndicadorCalificacion $registro): string => $registro->alumno_id.'-'.$registro->indicador_evaluacion_id);
        $indicadorIds = $destrezas
            ->flatMap(fn (Sys_Destreza $destreza) => $destreza->indicadores->pluck('id'))
            ->all();
        $filas = [];

        foreach ($alumnos as $alumno) {
            $celdas = [];

            foreach ($indicadorIds as $indicadorId) {
                $registro = $registros->get($alumno->id.'-'.$indicadorId);
                $celdas[(int) $indicadorId] = [
                    'calificacion' => $registro?->calificacion?->value,
                    'observacion' => $registro?->observacion,
                ];
            }

            $filas[] = [
                'id' => (int) $alumno->id,
                'nombre' => trim(($alumno->persona->apellidos ?? '').' '.($alumno->persona->nombres ?? '')),
                'indicadores' => $celdas,
                'calificaciones' => [],
            ];
        }

        return $filas;
    }

    /**
     * @param  array<int, array<int, array{calificacion: string, observacion: ?string, indicadores?: list<int>}>>  $filas
     */
    private function guardarDestrezas(ContextoDestreza $contexto, array $filas): void
    {
        $permitidos = $this->indicadoresPorDestreza($contexto);

        DB::transaction(function () use ($contexto, $filas, $permitidos): void {
            foreach ($filas as $alumnoId => $destrezas) {
                foreach ($destrezas as $destrezaId => $fila) {
                    EstablecimientoDestrezaCalificacion::query()->updateOrCreate(
                        [
                            'alumno_id' => $alumnoId,
                            'establecimiento_aula_id' => $contexto->aula->id,
                            'establecimiento_asignatura_id' => $contexto->asignatura->id,
                            'establecimiento_periodo_ciclo_id' => $contexto->ciclo->id,
                            'destreza_id' => $destrezaId,
                        ],
                        [
                            'establecimiento_id' => $contexto->aula->establecimiento_id,
                            'calificacion' => $fila['calificacion'],
                            'observacion' => $fila['observacion'],
                        ],
                    );
                    $this->sincronizarObservaciones($contexto, $alumnoId, $permitidos->get($destrezaId, []), $fila['indicadores'] ?? []);
                }
            }
        });
    }

    /**
     * @param  list<int>  $permitidos
     * @param  list<int>  $marcados
     */
    private function sincronizarObservaciones(ContextoDestreza $contexto, int $alumnoId, array $permitidos, array $marcados): void
    {
        if ($permitidos === []) {
            return;
        }

        $marcados = collect($marcados)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->intersect($permitidos)
            ->values();

        $consulta = EstablecimientoIndicadorObservacion::query()
            ->where('alumno_id', $alumnoId)
            ->where('establecimiento_aula_id', $contexto->aula->id)
            ->where('establecimiento_asignatura_id', $contexto->asignatura->id)
            ->where('establecimiento_periodo_ciclo_id', $contexto->ciclo->id)
            ->whereIn('indicador_evaluacion_id', $permitidos);

        if ($marcados->isNotEmpty()) {
            $consulta->whereNotIn('indicador_evaluacion_id', $marcados);
        }

        $consulta->delete();

        foreach ($marcados as $indicadorId) {
            EstablecimientoIndicadorObservacion::query()->updateOrCreate(
                [
                    'alumno_id' => $alumnoId,
                    'establecimiento_aula_id' => $contexto->aula->id,
                    'establecimiento_asignatura_id' => $contexto->asignatura->id,
                    'establecimiento_periodo_ciclo_id' => $contexto->ciclo->id,
                    'indicador_evaluacion_id' => $indicadorId,
                ],
                [
                    'establecimiento_id' => $contexto->aula->establecimiento_id,
                ],
            );
        }
    }

    /**
     * @param  array<int, array<int, array{calificacion: string, observacion: ?string}>>  $filas
     */
    private function guardarIndicadores(ContextoDestreza $contexto, array $filas): void
    {
        DB::transaction(function () use ($contexto, $filas): void {
            foreach ($filas as $alumnoId => $indicadores) {
                foreach ($indicadores as $indicadorId => $fila) {
                    EstablecimientoIndicadorCalificacion::query()->updateOrCreate(
                        [
                            'alumno_id' => $alumnoId,
                            'establecimiento_aula_id' => $contexto->aula->id,
                            'establecimiento_asignatura_id' => $contexto->asignatura->id,
                            'establecimiento_periodo_ciclo_id' => $contexto->ciclo->id,
                            'indicador_evaluacion_id' => $indicadorId,
                        ],
                        [
                            'establecimiento_id' => $contexto->aula->establecimiento_id,
                            'calificacion' => $fila['calificacion'],
                            'observacion' => $fila['observacion'],
                        ],
                    );
                }
            }
        });
    }

    private function usaAmbitos(ContextoDestreza $contexto): bool
    {
        $contexto->aula->loadMissing('establecimientoGrado.grado.subnivel');

        return (bool) $contexto->aula->establecimientoGrado?->grado?->subnivel?->usa_ambitos;
    }

    /**
     * @return Collection<int, Sys_Destreza>
     */
    private function catalogo(ContextoDestreza $contexto): Collection
    {
        if ($contexto->asignatura->asignatura_id === null || $contexto->asignatura->grado_id === null) {
            return collect();
        }

        return Sys_Destreza::query()
            ->where('asignatura_id', $contexto->asignatura->asignatura_id)
            ->where('grado_id', $contexto->asignatura->grado_id)
            ->where('activo', true)
            ->with([
                'criterio',
                'indicadores' => fn ($query) => $query->where('activo', true)->orderBy('orden')->orderBy('id'),
            ])
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }
}
