<?php

namespace App\Services;

use App\Models\Establecimiento;
use App\Models\EstablecimientoCierreActividadCalificacion;
use App\Models\EstablecimientoMejoraCalificacion;
use App\Models\EstablecimientoPeriodo;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MejoraCalificacion
{
    public function normativa(Establecimiento $establecimiento, EstablecimientoPeriodo $periodo): string
    {
        $inicio = CarbonImmutable::parse($periodo->fecha_inicio);
        $fin = CarbonImmutable::parse($periodo->fecha_fin);

        if ($establecimiento->regimen === 'Costa' && $fin->lte('2027-03-01')) {
            return '2024';
        }

        if ($establecimiento->regimen === 'Sierra' && $inicio->lt('2026-09-01')) {
            return '2024';
        }

        return '2026';
    }

    public function tipo(float $nota): ?string
    {
        if ($nota > 7.0 && $nota < 9.0) {
            return 'directa';
        }

        if ($nota > 0.0 && $nota < 7.0) {
            return 'refuerzo';
        }

        return null;
    }

    public function calcular(float $inicial, ?float $notaRefuerzo, float $adicional, string $tipo, string $normativa): float
    {
        $promedio = match (true) {
            $normativa === '2024' && $tipo === 'refuerzo' && $notaRefuerzo !== null => ($inicial + $notaRefuerzo + $adicional) / 3,
            default => ($inicial + $adicional) / 2,
        };

        if ($promedio < $inicial) {
            $promedio = $inicial;
        }

        if ($tipo === 'directa') {
            $promedio = min(9.0, $promedio);
        }

        if ($normativa === '2026' && $tipo === 'refuerzo') {
            $promedio = min(7.0, $promedio);
        }

        return round($promedio, 2);
    }

    /**
     * @param  array{nota_refuerzo: float|null, nota_adicional: float, fecha_notificacion: string, fecha_solicitud: string, fecha_evaluacion: string, referencia_solicitud: string}  $datos
     */
    public function registrar(
        EstablecimientoCierreActividadCalificacion $calificacion,
        User $docente,
        array $datos,
    ): EstablecimientoMejoraCalificacion {
        return DB::transaction(function () use ($calificacion, $docente, $datos): EstablecimientoMejoraCalificacion {
            $calificacion = EstablecimientoCierreActividadCalificacion::query()
                ->with(['actividad.aula.establecimiento', 'actividad.aula.periodo', 'actividad.asignatura'])
                ->lockForUpdate()
                ->findOrFail($calificacion->id);
            $actividad = $calificacion->actividad;
            $establecimiento = $actividad->aula->establecimiento;
            $periodo = $actividad->aula->periodo;
            $asignatura = $actividad->asignatura;
            $notaInicial = (float) $calificacion->nota;
            $tipo = $this->tipo($notaInicial);
            $normativa = $this->normativa($establecimiento, $periodo);

            if ($tipo === null || $asignatura->escala->value !== 'nacional') {
                throw ValidationException::withMessages([
                    'calificacion_id' => 'Esta evaluación no cumple los rangos para una mejora de calificación.',
                ]);
            }

            if ($calificacion->mejora()->exists()) {
                throw ValidationException::withMessages([
                    'calificacion_id' => 'Esta calificación ya tiene registrada una mejora.',
                ]);
            }

            if ($tipo === 'refuerzo' && $normativa === '2024' && $datos['nota_refuerzo'] === null) {
                throw ValidationException::withMessages([
                    'nota_refuerzo' => 'La normativa aplicable requiere registrar la calificación del refuerzo pedagógico.',
                ]);
            }

            $fechaNotificacion = CarbonImmutable::parse($datos['fecha_notificacion']);
            $fechaSolicitud = CarbonImmutable::parse($datos['fecha_solicitud']);
            $fechaEvaluacion = CarbonImmutable::parse($datos['fecha_evaluacion']);
            $fechaFueraDelPlazoNacional = $normativa === '2024'
                && $fechaSolicitud->gt($this->sumarDiasLaborables($fechaNotificacion, 2));
            $evaluacionAntesDeSolicitud = $fechaEvaluacion->lt($fechaSolicitud);
            $evaluacionFueraDelPlazoDeRefuerzo = $normativa === '2024'
                && $tipo === 'refuerzo'
                && $fechaEvaluacion->gt($fechaSolicitud->addDays(5));

            if ($fechaSolicitud->lt($fechaNotificacion) || $fechaFueraDelPlazoNacional) {
                throw ValidationException::withMessages([
                    'fecha_solicitud' => $normativa === '2024'
                        ? 'La solicitud debe presentarse dentro de los dos días hábiles siguientes a la notificación.'
                        : 'La solicitud no puede estar fechada antes de la notificación de la calificación.',
                ]);
            }

            if ($evaluacionAntesDeSolicitud || $evaluacionFueraDelPlazoDeRefuerzo) {
                throw ValidationException::withMessages([
                    'fecha_evaluacion' => $normativa === '2024' && $tipo === 'refuerzo'
                        ? 'La evaluación de refuerzo debe aplicarse dentro de los cinco días posteriores a la solicitud.'
                        : 'La evaluación adicional no puede ser anterior a la solicitud.',
                ]);
            }

            $establecimientoBloqueo = Establecimiento::query()->lockForUpdate()->findOrFail($establecimiento->id);
            $this->validarCupos($calificacion, $tipo, $normativa, $establecimientoBloqueo, $periodo);
            $notaFinal = $this->calcular(
                $notaInicial,
                $datos['nota_refuerzo'],
                $datos['nota_adicional'],
                $tipo,
                $normativa,
            );

            return EstablecimientoMejoraCalificacion::query()->create([
                'establecimiento_cierre_actividad_calificacion_id' => $calificacion->id,
                'tipo_mejora' => $tipo,
                'nota_inicial' => $notaInicial,
                'nota_refuerzo' => $datos['nota_refuerzo'],
                'nota_adicional' => $datos['nota_adicional'],
                'nota_final' => $notaFinal,
                'fecha_notificacion' => $datos['fecha_notificacion'],
                'fecha_solicitud' => $datos['fecha_solicitud'],
                'fecha_evaluacion' => $datos['fecha_evaluacion'],
                'referencia_solicitud' => $datos['referencia_solicitud'],
                'normativa_aplicada' => $normativa,
                'registrado_por' => $docente->id,
            ]);
        });
    }

    private function validarCupos(
        EstablecimientoCierreActividadCalificacion $calificacion,
        string $tipo,
        string $normativa,
        Establecimiento $establecimiento,
        EstablecimientoPeriodo $periodo,
    ): void {
        $mejorasDelCiclo = EstablecimientoMejoraCalificacion::query()
            ->where('tipo_mejora', $tipo)
            ->whereHas('calificacion', function (Builder $query) use ($calificacion, $establecimiento, $periodo): void {
                $query->where('alumno_id', $calificacion->alumno_id)
                    ->whereHas('actividad.aula', function (Builder $query) use ($establecimiento, $periodo): void {
                        $query->where('establecimiento_id', $establecimiento->id)
                            ->where('establecimiento_periodo_id', $periodo->id);
                    });
            });

        $cuposUsados = (clone $mejorasDelCiclo)->count();
        $maximoTotal = $tipo === 'directa' ? 3 : 6;

        if ($cuposUsados >= $maximoTotal) {
            throw ValidationException::withMessages([
                'calificacion_id' => 'El estudiante ya alcanzó el máximo anual de mejoras de este tipo.',
            ]);
        }

        $porAsignatura = (clone $mejorasDelCiclo)
            ->whereHas('calificacion', function (Builder $query) use ($calificacion): void {
                $query->whereHas('actividad', fn (Builder $query) => $query->where('establecimiento_asignatura_id', $calificacion->actividad->establecimiento_asignatura_id));
            });

        if ($tipo === 'directa' && $normativa === '2026' && $porAsignatura->count() >= 1) {
            throw ValidationException::withMessages([
                'calificacion_id' => 'En esta asignatura ya se utilizó la mejora directa anual.',
            ]);
        }

        if ($tipo === 'directa' && $normativa === '2024') {
            $mismoPeriodo = (clone $porAsignatura)
                ->whereHas('calificacion.actividad', fn (Builder $query) => $query->where('establecimiento_periodo_ciclo_id', $calificacion->actividad->establecimiento_periodo_ciclo_id));

            if ($mismoPeriodo->exists()) {
                throw ValidationException::withMessages([
                    'calificacion_id' => 'Ya se utilizó la mejora directa de esta asignatura en el periodo académico.',
                ]);
            }
        }

        $maximoPorAsignatura = $tipo === 'directa' ? 1 : 2;

        if ($tipo === 'refuerzo' && $porAsignatura->count() >= $maximoPorAsignatura) {
            throw ValidationException::withMessages([
                'calificacion_id' => 'El estudiante ya alcanzó el máximo de mejoras de refuerzo para esta asignatura.',
            ]);
        }
    }

    private function sumarDiasLaborables(CarbonImmutable $fecha, int $dias): CarbonImmutable
    {
        $diasSumados = 0;

        while ($diasSumados < $dias) {
            $fecha = $fecha->addDay();

            if (! $fecha->isWeekend()) {
                $diasSumados++;
            }
        }

        return $fecha->endOfDay();
    }
}
