<?php

namespace App\Services;

use App\Auth\ContextoComportamiento;
use App\Models\Alumno;
use App\Models\EquivalenciaDetalle;
use App\Models\EstablecimientoComportamiento;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Evaluación cualitativa del comportamiento.
 *
 * El Reglamento General a la LOEI la expresa con las letras A, B, C, D y E,
 * según el cumplimiento de los compromisos de convivencia. No forma parte
 * del promedio de las asignaturas.
 */
class RegistroComportamiento
{
    public function __construct(private EscalaComportamiento $escala) {}

    /**
     * @param  Collection<int, Alumno>  $alumnos
     * @return array{
     *     escala: list<array{id: int, valor: string, nombre: string, descripcion: ?string, exige_observacion: bool}>,
     *     exige: list<string>,
     *     alumnos: list<array{id: int, nombre: string, calificacion: ?string, observacion: ?string}>
     * }
     */
    public function presentar(ContextoComportamiento $contexto, Collection $alumnos): array
    {
        $escala = $this->escala->delPeriodo((int) $contexto->aula->establecimiento_periodo_id);
        $registros = EstablecimientoComportamiento::query()
            ->where('establecimiento_aula_id', $contexto->aula->id)
            ->where('establecimiento_periodo_ciclo_id', $contexto->ciclo->id)
            ->get()
            ->keyBy(fn (EstablecimientoComportamiento $registro): int => (int) $registro->alumno_id);

        $escalaFilas = $escala === null
            ? []
            : $escala->detalles->map(fn (EquivalenciaDetalle $detalle): array => [
                'id' => (int) $detalle->id,
                'valor' => (string) $detalle->valor,
                'nombre' => $detalle->nombre,
                'descripcion' => $detalle->descripcion,
                'exige_observacion' => (bool) $detalle->exige_observacion,
            ])->values()->all();
        $filas = [];

        foreach ($alumnos as $alumno) {
            $registro = $registros->get((int) $alumno->id);

            $filas[] = [
                'id' => (int) $alumno->id,
                'nombre' => trim(($alumno->persona->apellidos ?? '').' '.($alumno->persona->nombres ?? '')),
                'calificacion' => $registro?->equivalencia_detalle_id === null ? null : (string) $registro->equivalencia_detalle_id,
                'observacion' => $registro?->observacion,
            ];
        }

        return [
            'escala' => $escalaFilas,
            'exige' => collect($escalaFilas)
                ->where('exige_observacion', true)
                ->map(fn (array $detalle): string => (string) $detalle['id'])
                ->values()
                ->all(),
            'alumnos' => $filas,
        ];
    }

    /**
     * @param  array<int, array{equivalencia_detalle_id: int, observacion: ?string}>  $filas
     */
    public function guardar(ContextoComportamiento $contexto, array $filas): void
    {
        DB::transaction(function () use ($contexto, $filas): void {
            foreach ($filas as $alumnoId => $fila) {
                EstablecimientoComportamiento::query()->updateOrCreate(
                    [
                        'alumno_id' => $alumnoId,
                        'establecimiento_aula_id' => $contexto->aula->id,
                        'establecimiento_periodo_ciclo_id' => $contexto->ciclo->id,
                    ],
                    [
                        'establecimiento_id' => $contexto->aula->establecimiento_id,
                        'equivalencia_detalle_id' => $fila['equivalencia_detalle_id'],
                        'observacion' => $fila['observacion'],
                    ],
                );
            }
        });
    }
}
