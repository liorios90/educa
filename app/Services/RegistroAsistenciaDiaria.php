<?php

namespace App\Services;

use App\Auth\ContextoAsistencia;
use App\Enums\EstadoAsistencia;
use App\Enums\MotivoJustificacionAsistencia;
use App\Models\Alumno;
use App\Models\EstablecimientoAsistencia;
use App\Models\HorarioClase;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Registro de asistencia de la hora de clase.
 *
 * El Reglamento General a la LOEI impide la promoción cuando las faltas
 * injustificadas superan el 10% de las horas de clase del año lectivo.
 * La falta justificada exige certificado médico, calamidad doméstica,
 * representación del plantel o citación de autoridad. El atraso se registra
 * aparte y no entra en ese porcentaje.
 */
class RegistroAsistenciaDiaria
{
    public const LIMITE_FALTAS_INJUSTIFICADAS = 10.0;

    /**
     * @var array<string, array<int, int>>
     */
    private array $horasPorDia = [];

    public function hoyLectivo(): Carbon
    {
        return now('America/Guayaquil')->startOfDay();
    }

    public function evaluarFecha(ContextoAsistencia $contexto, CarbonInterface $fecha): ?string
    {
        $dia = $fecha->toDateString();
        $periodo = $contexto->aula->periodo;

        if ($dia > $this->hoyLectivo()->toDateString()) {
            return 'No se puede registrar la asistencia de un día que todavía no llega.';
        }

        if ($periodo?->fecha_inicio !== null && $dia < $periodo->fecha_inicio->toDateString()) {
            return 'La fecha está fuera del año lectivo.';
        }

        if ($periodo?->fecha_fin !== null && $dia > $periodo->fecha_fin->toDateString()) {
            return 'La fecha está fuera del año lectivo.';
        }

        $dias = array_keys($this->horasDe($contexto));

        if ($dias !== [] && ! in_array($fecha->dayOfWeekIso, $dias, true)) {
            $motivo = $contexto->modo->esJornada()
                ? 'Esa fecha no tiene clase en esta aula.'
                : 'Esa fecha no tiene hora de clase de esta asignatura.';

            return $motivo.' El horario marca '.$this->textoDias($dias).'.';
        }

        return null;
    }

    /**
     * @param  Collection<int, Alumno>  $alumnos
     * @return array{
     *     fecha: string,
     *     fecha_min: ?string,
     *     fecha_max: ?string,
     *     mensaje_fecha: ?string,
     *     puede_registrar: bool,
     *     base: string,
     *     alumnos: list<array{
     *         id: int,
     *         nombre: string,
     *         estado: string,
     *         justificacion: ?string,
     *         registrada: bool,
     *         parcial: array{presentes: int, faltas: int, justificadas: int, atrasos: int},
     *         anual: array{faltas: int, horas: int, porcentaje: ?float, excede: bool}
     *     }>,
     *     jornada: array{registrada: bool, presentes: int, faltas: int, justificadas: int, atrasos: int}
     * }
     */
    public function presentar(ContextoAsistencia $contexto, CarbonInterface $fecha, Collection $alumnos): array
    {
        $periodo = $contexto->aula->periodo;
        $hoy = $this->hoyLectivo()->toDateString();
        $fin = $periodo?->fecha_fin?->toDateString();
        $registros = $this->registros($contexto);
        $porAlumno = $registros->groupBy(fn (EstablecimientoAsistencia $asistencia): int => (int) $asistencia->alumno_id);
        $control = $this->controlAnual($contexto, $registros);
        $mensajeFecha = $this->evaluarFecha($contexto, $fecha);
        $fechaDia = $fecha->toDateString();
        $filas = [];
        $jornada = [
            'registrada' => false,
            'presentes' => 0,
            'faltas' => 0,
            'justificadas' => 0,
            'atrasos' => 0,
        ];

        foreach ($alumnos as $alumno) {
            /** @var Collection<int, EstablecimientoAsistencia> $delAlumno */
            $delAlumno = $porAlumno->get((int) $alumno->id, collect());
            $delDia = $delAlumno->first(
                fn (EstablecimientoAsistencia $asistencia): bool => $asistencia->fecha?->toDateString() === $fechaDia,
            );
            $delParcial = $delAlumno->filter(
                fn (EstablecimientoAsistencia $asistencia): bool => $contexto->ciclo !== null
                    && (int) $asistencia->establecimiento_periodo_ciclo_id === (int) $contexto->ciclo->id
                    && (int) $asistencia->parcial_numero === (int) $contexto->parcialNumero,
            );
            $anual = $this->resumenAnual($delAlumno, $control);

            if ($delDia instanceof EstablecimientoAsistencia) {
                $jornada['registrada'] = true;
                $this->sumarEstado($jornada, $delDia->estado);
            }

            $filas[] = [
                'id' => (int) $alumno->id,
                'nombre' => trim(($alumno->persona->apellidos ?? '').' '.($alumno->persona->nombres ?? '')),
                'estado' => $delDia?->estado->value ?? EstadoAsistencia::Presente->value,
                'justificacion' => $delDia?->justificacion?->value,
                'registrada' => $delDia instanceof EstablecimientoAsistencia,
                'parcial' => $this->resumenEstados($delParcial),
                'anual' => $anual,
            ];
        }

        return [
            'fecha' => $fechaDia,
            'fecha_min' => $periodo?->fecha_inicio?->toDateString(),
            'fecha_max' => $fin === null || $fin > $hoy ? $hoy : $fin,
            'mensaje_fecha' => $mensajeFecha,
            'puede_registrar' => $mensajeFecha === null && $alumnos->isNotEmpty(),
            'base' => $control['base'],
            'alumnos' => $filas,
            'jornada' => $jornada,
        ];
    }

    /**
     * @param  array<int, array{estado: string, justificacion: ?string}>  $filas
     */
    public function guardar(ContextoAsistencia $contexto, CarbonInterface $fecha, array $filas): void
    {
        DB::transaction(function () use ($contexto, $fecha, $filas): void {
            $esJornada = $contexto->modo->esJornada();

            foreach ($filas as $alumnoId => $fila) {
                $estado = EstadoAsistencia::from($fila['estado']);
                $datos = [
                    'establecimiento_id' => $contexto->aula->establecimiento_id,
                    'establecimiento_aula_id' => $contexto->aula->id,
                    'establecimiento_asignatura_id' => $esJornada ? null : $contexto->asignatura?->id,
                    'establecimiento_periodo_ciclo_id' => $esJornada ? null : $contexto->ciclo?->id,
                    'parcial_numero' => $esJornada ? null : $contexto->parcialNumero,
                    'alcance' => $contexto->modo,
                    'estado' => $estado,
                    'justificacion' => $estado->exigeJustificacion() && $fila['justificacion'] !== null
                        ? MotivoJustificacionAsistencia::from($fila['justificacion'])
                        : null,
                ];
                $existente = EstablecimientoAsistencia::query()
                    ->where('alumno_id', $alumnoId)
                    ->where('establecimiento_aula_id', $contexto->aula->id)
                    ->whereDate('fecha', $fecha->toDateString())
                    ->when(
                        $esJornada,
                        fn ($query) => $query->whereNull('establecimiento_asignatura_id'),
                        fn ($query) => $query->where('establecimiento_asignatura_id', $contexto->asignatura?->id),
                    )
                    ->first();

                if ($existente instanceof EstablecimientoAsistencia) {
                    $existente->update($datos);

                    continue;
                }

                EstablecimientoAsistencia::query()->create([
                    ...$datos,
                    'alumno_id' => $alumnoId,
                    'fecha' => $fecha->toDateString(),
                ]);
            }
        });
    }

    /**
     * @return array<int, int>
     */
    private function horasDe(ContextoAsistencia $contexto): array
    {
        $clave = $contexto->aula->id.':'.($contexto->modo->esJornada() ? 'jornada' : $contexto->asignatura?->id);

        if (isset($this->horasPorDia[$clave])) {
            return $this->horasPorDia[$clave];
        }

        $conteo = [];

        $clases = HorarioClase::query()
            ->where('establecimiento_aula_id', $contexto->aula->id)
            ->when(
                ! $contexto->modo->esJornada(),
                fn ($query) => $query->where('establecimiento_asignatura_id', $contexto->asignatura?->id),
            )
            ->get(['dia_semana']);

        foreach ($clases as $clase) {
            $dia = (int) $clase->dia_semana;
            $conteo[$dia] = $contexto->modo->esJornada() ? 1 : ($conteo[$dia] ?? 0) + 1;
        }

        return $this->horasPorDia[$clave] = $conteo;
    }

    /**
     * @return Collection<int, EstablecimientoAsistencia>
     */
    private function registros(ContextoAsistencia $contexto): Collection
    {
        $periodo = $contexto->aula->periodo;

        return EstablecimientoAsistencia::query()
            ->where('establecimiento_id', $contexto->aula->establecimiento_id)
            ->where('establecimiento_aula_id', $contexto->aula->id)
            ->when(
                $contexto->modo->esJornada(),
                fn ($query) => $query->whereNull('establecimiento_asignatura_id'),
                fn ($query) => $query->where('establecimiento_asignatura_id', $contexto->asignatura?->id),
            )
            ->when(
                $periodo?->fecha_inicio !== null,
                fn ($query) => $query->whereDate('fecha', '>=', $periodo->fecha_inicio),
            )
            ->when(
                $periodo?->fecha_fin !== null,
                fn ($query) => $query->whereDate('fecha', '<=', $periodo->fecha_fin),
            )
            ->get();
    }

    /**
     * @param  Collection<int, EstablecimientoAsistencia>  $registros
     * @return array{base: string, horas: int, horas_por_dia: array<int, int>}
     */
    private function controlAnual(ContextoAsistencia $contexto, Collection $registros): array
    {
        $periodo = $contexto->aula->periodo;
        $horasPorDia = $this->horasDe($contexto);
        $inicio = $periodo?->fecha_inicio;
        $fin = $periodo?->fecha_fin;

        if ($horasPorDia !== [] && $inicio !== null && $fin !== null && $fin->greaterThanOrEqualTo($inicio)) {
            return [
                'base' => 'horario',
                'horas' => $this->contarHoras($inicio, $fin, $horasPorDia),
                'horas_por_dia' => $horasPorDia,
            ];
        }

        return [
            'base' => 'registradas',
            'horas' => $registros
                ->map(fn (EstablecimientoAsistencia $asistencia): string => $asistencia->fecha?->toDateString() ?? '')
                ->filter()
                ->unique()
                ->count(),
            'horas_por_dia' => [],
        ];
    }

    /**
     * @param  array<int, int>  $horasPorDia
     */
    private function contarHoras(CarbonInterface $inicio, CarbonInterface $fin, array $horasPorDia): int
    {
        $total = 0;

        foreach (CarbonPeriod::create($inicio->toDateString(), $fin->toDateString()) as $dia) {
            $total += (int) ($horasPorDia[$dia->dayOfWeekIso] ?? 0);
        }

        return $total;
    }

    /**
     * @param  Collection<int, EstablecimientoAsistencia>  $registros
     * @param  array{base: string, horas: int, horas_por_dia: array<int, int>}  $control
     * @return array{faltas: int, horas: int, porcentaje: ?float, excede: bool}
     */
    private function resumenAnual(Collection $registros, array $control): array
    {
        $faltas = 0;

        foreach ($registros as $registro) {
            if ($registro->estado !== EstadoAsistencia::Falta) {
                continue;
            }

            if ($control['base'] === 'horario') {
                $dia = (int) $registro->fecha?->dayOfWeekIso;
                $faltas += max(1, (int) ($control['horas_por_dia'][$dia] ?? 1));

                continue;
            }

            $faltas++;
        }

        if ($control['horas'] === 0) {
            return [
                'faltas' => $faltas,
                'horas' => 0,
                'porcentaje' => null,
                'excede' => false,
            ];
        }

        $porcentaje = round(($faltas / $control['horas']) * 100, 2);

        return [
            'faltas' => $faltas,
            'horas' => $control['horas'],
            'porcentaje' => $porcentaje,
            'excede' => $porcentaje > self::LIMITE_FALTAS_INJUSTIFICADAS,
        ];
    }

    /**
     * @param  Collection<int, EstablecimientoAsistencia>  $registros
     * @return array{presentes: int, faltas: int, justificadas: int, atrasos: int}
     */
    private function resumenEstados(Collection $registros): array
    {
        $conteo = [
            'presentes' => 0,
            'faltas' => 0,
            'justificadas' => 0,
            'atrasos' => 0,
        ];

        foreach ($registros as $registro) {
            $this->sumarEstado($conteo, $registro->estado);
        }

        return $conteo;
    }

    /**
     * @param  array{presentes: int, faltas: int, justificadas: int, atrasos: int}  $conteo
     */
    private function sumarEstado(array &$conteo, EstadoAsistencia $estado): void
    {
        match ($estado) {
            EstadoAsistencia::Presente => $conteo['presentes']++,
            EstadoAsistencia::Falta => $conteo['faltas']++,
            EstadoAsistencia::Justificada => $conteo['justificadas']++,
            EstadoAsistencia::Atraso => $conteo['atrasos']++,
        };
    }

    /**
     * @param  list<int>  $dias
     */
    private function textoDias(array $dias): string
    {
        $nombres = [
            1 => 'lunes',
            2 => 'martes',
            3 => 'miércoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sábado',
            7 => 'domingo',
        ];

        $texto = collect($dias)
            ->sort()
            ->map(fn (int $dia): string => 'el '.$nombres[$dia])
            ->values();

        if ($texto->count() <= 1) {
            return (string) $texto->first();
        }

        $ultimo = $texto->pop();

        return $texto->join(', ').' y '.$ultimo;
    }
}
