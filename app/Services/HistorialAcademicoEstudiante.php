<?php

namespace App\Services;

use App\Auth\DocenteContexto;
use App\Enums\EscalaCalificacion;
use App\Enums\EstadoAsistencia;
use App\Models\Alumno;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsistencia;
use App\Models\EstablecimientoCalificacion;
use App\Models\EstablecimientoInsumoActividadCalificacion;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Support\Collection;
use RuntimeException;

class HistorialAcademicoEstudiante
{
    public function de(Alumno $alumno, DocenteContexto $contexto, Establecimiento $establecimiento): HistorialAcademico
    {
        $historial = $this->deVarios(collect([$alumno]), $contexto, $establecimiento)->first();

        if (! $historial instanceof HistorialAcademico) {
            throw new RuntimeException('No se pudo construir el historial académico del estudiante.');
        }

        return $historial;
    }

    /**
     * @param  Collection<int, Alumno>  $alumnos
     * @return Collection<int, HistorialAcademico>
     */
    public function deVarios(Collection $alumnos, DocenteContexto $contexto, Establecimiento $establecimiento): Collection
    {
        $alumnos->each(function (Alumno $alumno): void {
            $alumno->loadMissing('persona');
        });
        $contexto->aula->loadMissing('periodo');
        $periodo = $contexto->aula->periodo;
        $escala = $contexto->asignatura->escala ?? EscalaCalificacion::Nacional;
        $ids = $alumnos->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
        $notasInsumo = $this->notasInsumoDe($ids, $contexto);
        $calificaciones = $this->calificacionesPeriodoDe($ids, $periodo);
        $asistencias = $this->asistenciasDe($ids, $establecimiento, $periodo);

        return $alumnos
            ->map(fn (Alumno $alumno): HistorialAcademico => new HistorialAcademico(
                estudiante: trim(($alumno->persona->apellidos ?? '').' '.($alumno->persona->nombres ?? '')),
                aula: $contexto->aula->etiqueta(),
                asignatura: $contexto->asignatura->nombre(),
                periodo: (string) ($periodo?->nombre ?? ''),
                ciclo: $contexto->ciclo->nombre,
                parcial: $contexto->parcialNumero,
                escala: $escala->label(),
                notaMinimaAprobacion: $escala === EscalaCalificacion::BachilleratoInternacional ? 4.0 : 7.0,
                notasInsumo: $notasInsumo->get((int) $alumno->id, []),
                calificacionesPeriodo: $calificaciones->get((int) $alumno->id, []),
                asistencias: $asistencias->get((int) $alumno->id, []),
            ))
            ->values();
    }

    /**
     * @param  list<int>  $alumnoIds
     * @return Collection<int, list<array{fecha: string, actividad: string, insumo: string, asignatura: string, ciclo: string, parcial: int, nota: string}>>
     */
    private function notasInsumoDe(array $alumnoIds, DocenteContexto $contexto): Collection
    {
        if ($alumnoIds === []) {
            return collect();
        }

        return EstablecimientoInsumoActividadCalificacion::query()
            ->with([
                'actividad.ciclo',
                'actividad.detalle',
                'actividad.asignatura.asignatura',
                'actividad.asignatura.asignaturaPropia',
            ])
            ->whereIn('alumno_id', $alumnoIds)
            ->whereHas('actividad', function ($query) use ($contexto): void {
                $query
                    ->where('establecimiento_asignatura_id', $contexto->asignatura->id)
                    ->where('establecimiento_aula_id', $contexto->aula->id);
            })
            ->get()
            ->groupBy(fn (EstablecimientoInsumoActividadCalificacion $calificacion): int => (int) $calificacion->alumno_id)
            ->map(function (Collection $grupo): array {
                return $grupo
                    ->sortBy(fn (EstablecimientoInsumoActividadCalificacion $calificacion): string => $calificacion->actividad?->created_at?->toDateTimeString() ?? '')
                    ->values()
                    ->map(function (EstablecimientoInsumoActividadCalificacion $calificacion): array {
                        $actividad = $calificacion->actividad;

                        return [
                            'fecha' => $actividad?->created_at?->toDateString() ?? '',
                            'actividad' => (string) ($actividad?->nombre ?? ''),
                            'insumo' => (string) ($actividad?->detalle?->nombre ?? ''),
                            'asignatura' => $actividad?->asignatura?->nombre() ?? '',
                            'ciclo' => (string) ($actividad?->ciclo?->nombre ?? ''),
                            'parcial' => (int) ($actividad?->parcial_numero ?? 0),
                            'nota' => (string) $calificacion->nota,
                        ];
                    })
                    ->all();
            });
    }

    /**
     * @param  list<int>  $alumnoIds
     * @return Collection<int, list<array{asignatura: string, ciclo: int, parcial: int, nota: string}>>
     */
    private function calificacionesPeriodoDe(array $alumnoIds, ?EstablecimientoPeriodo $periodo): Collection
    {
        if ($alumnoIds === [] || $periodo === null) {
            return collect();
        }

        return EstablecimientoCalificacion::query()
            ->with(['asignatura.asignatura', 'asignatura.asignaturaPropia'])
            ->whereIn('alumno_id', $alumnoIds)
            ->where('establecimiento_periodo_id', $periodo->id)
            ->orderBy('ciclo_orden')
            ->orderBy('parcial_numero')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (EstablecimientoCalificacion $calificacion): int => (int) $calificacion->alumno_id)
            ->map(fn (Collection $grupo): array => $grupo
                ->map(fn (EstablecimientoCalificacion $calificacion): array => [
                    'asignatura' => $calificacion->asignatura?->nombre() ?? '',
                    'ciclo' => (int) $calificacion->ciclo_orden,
                    'parcial' => (int) $calificacion->parcial_numero,
                    'nota' => (string) $calificacion->nota,
                ])
                ->values()
                ->all());
    }

    /**
     * @param  list<int>  $alumnoIds
     * @return Collection<int, list<array{fecha: string, estado: string, asignatura: string|null}>>
     */
    private function asistenciasDe(array $alumnoIds, Establecimiento $establecimiento, ?EstablecimientoPeriodo $periodo): Collection
    {
        if ($alumnoIds === []) {
            return collect();
        }

        return EstablecimientoAsistencia::query()
            ->with(['asignatura.asignatura', 'asignatura.asignaturaPropia'])
            ->where('establecimiento_id', $establecimiento->id)
            ->whereIn('alumno_id', $alumnoIds)
            ->when(
                $periodo?->fecha_inicio !== null,
                fn ($query) => $query->whereDate('fecha', '>=', $periodo->fecha_inicio),
            )
            ->when(
                $periodo?->fecha_fin !== null,
                fn ($query) => $query->whereDate('fecha', '<=', $periodo->fecha_fin),
            )
            ->orderBy('fecha')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (EstablecimientoAsistencia $asistencia): int => (int) $asistencia->alumno_id)
            ->map(fn (Collection $grupo): array => $grupo
                ->map(fn (EstablecimientoAsistencia $asistencia): array => [
                    'fecha' => $asistencia->fecha?->toDateString() ?? '',
                    'estado' => $asistencia->estado instanceof EstadoAsistencia
                        ? $asistencia->estado->label()
                        : (string) $asistencia->estado,
                    'asignatura' => $asistencia->asignatura?->nombre(),
                ])
                ->values()
                ->all());
    }
}
