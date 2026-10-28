<?php

namespace App\Services;

use App\Auth\ContextoComportamiento;
use App\Models\Alumno;
use App\Models\EquivalenciaDetalle;
use App\Models\EstablecimientoComportamiento;
use App\Models\EstablecimientoPeriodo;
use App\Models\Sys_CriterioComportamiento;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Evaluación cualitativa del comportamiento por indicador.
 *
 * Cada habilidad elegida para el periodo recibe un valor de la tabla
 * de equivalencias de comportamiento. Esa calificación no forma parte
 * del promedio de las asignaturas.
 */
class RegistroComportamiento
{
    public function __construct(private EscalaComportamiento $escala) {}

    /**
     * @param  Collection<int, Alumno>  $alumnos
     * @return array{
     *     escala: list<array{id: int, codigo: string, nombre: string, descripcion: ?string, jerarquia: ?int, exige_observacion: bool}>,
     *     exige: list<string>,
     *     indicadores: list<array{id: int, codigo: string, descripcion: string}>,
     *     alumnos: list<array{id: int, nombre: string, indicadores: array<int, array{calificacion: ?string, observacion: ?string}>}>
     * }
     */
    public function presentar(ContextoComportamiento $contexto, Collection $alumnos): array
    {
        $escala = $this->escala->delPeriodo((int) $contexto->aula->establecimiento_periodo_id);
        $indicadores = EstablecimientoPeriodo::query()
            ->find($contexto->aula->establecimiento_periodo_id)
            ?->criteriosComportamiento()
            ->orderBy('codigo')
            ->get() ?? collect();
        $registros = EstablecimientoComportamiento::query()
            ->where('establecimiento_aula_id', $contexto->aula->id)
            ->where('establecimiento_asignatura_id', $contexto->asignatura->id)
            ->where('establecimiento_periodo_ciclo_id', $contexto->ciclo->id)
            ->get()
            ->keyBy(fn (EstablecimientoComportamiento $registro): string => $registro->alumno_id.'-'.$registro->criterio_comportamiento_id);

        $escalaFilas = $escala === null
            ? []
            : $escala->detalles->map(fn (EquivalenciaDetalle $detalle): array => [
                'id' => (int) $detalle->id,
                'codigo' => (string) $detalle->valor,
                'nombre' => $detalle->nombre,
                'descripcion' => $detalle->descripcion,
                'jerarquia' => $detalle->jerarquia,
                'exige_observacion' => (bool) $detalle->exige_observacion,
            ])->values()->all();
        $indicadorFilas = $indicadores->map(fn (Sys_CriterioComportamiento $criterio): array => [
            'id' => (int) $criterio->id,
            'codigo' => $criterio->codigo,
            'descripcion' => $criterio->descripcion,
        ])->values()->all();
        $filas = [];

        foreach ($alumnos as $alumno) {
            $celdas = [];

            foreach ($indicadorFilas as $indicador) {
                $registro = $registros->get($alumno->id.'-'.$indicador['id']);
                $celdas[$indicador['id']] = [
                    'calificacion' => $registro?->equivalencia_detalle_id === null ? null : (string) $registro->equivalencia_detalle_id,
                    'observacion' => $registro?->observacion,
                ];
            }

            $filas[] = [
                'id' => (int) $alumno->id,
                'nombre' => trim(($alumno->persona->apellidos ?? '').' '.($alumno->persona->nombres ?? '')),
                'indicadores' => $celdas,
            ];
        }

        return [
            'escala' => $escalaFilas,
            'exige' => collect($escalaFilas)
                ->where('exige_observacion', true)
                ->map(fn (array $detalle): string => (string) $detalle['id'])
                ->values()
                ->all(),
            'indicadores' => $indicadorFilas,
            'alumnos' => $filas,
        ];
    }

    /**
     * @param  array<int, array<int, array{equivalencia_detalle_id: int, observacion: ?string}>>  $filas
     */
    public function guardar(ContextoComportamiento $contexto, array $filas): void
    {
        DB::transaction(function () use ($contexto, $filas): void {
            $criterios = [];

            foreach ($filas as $alumnoId => $indicadores) {
                foreach ($indicadores as $criterioId => $fila) {
                    $criterios[] = $criterioId;

                    EstablecimientoComportamiento::query()->updateOrCreate(
                        [
                            'alumno_id' => $alumnoId,
                            'establecimiento_aula_id' => $contexto->aula->id,
                            'establecimiento_asignatura_id' => $contexto->asignatura->id,
                            'establecimiento_periodo_ciclo_id' => $contexto->ciclo->id,
                            'criterio_comportamiento_id' => $criterioId,
                        ],
                        [
                            'establecimiento_id' => $contexto->aula->establecimiento_id,
                            'equivalencia_detalle_id' => $fila['equivalencia_detalle_id'],
                            'observacion' => $fila['observacion'],
                        ],
                    );
                }
            }

            EstablecimientoComportamiento::query()
                ->where('establecimiento_aula_id', $contexto->aula->id)
                ->where('establecimiento_asignatura_id', $contexto->asignatura->id)
                ->where('establecimiento_periodo_ciclo_id', $contexto->ciclo->id)
                ->when($criterios !== [], fn ($query) => $query->whereNotIn('criterio_comportamiento_id', array_unique($criterios)))
                ->delete();
        });
    }
}
