<?php

namespace App\Services;

use App\Enums\EscalaCalificacion;
use App\Enums\EsquemaCiclo;
use App\Enums\EstadoAsistencia;
use App\Enums\TipoCalificacion;
use App\Enums\TipoCierreEvaluacion;
use App\Enums\UsoEquivalencia;
use App\Models\Alumno;
use App\Models\Equivalencia;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAsistencia;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoCierreActividad;
use App\Models\EstablecimientoComportamiento;
use App\Models\EstablecimientoInsumoActividad;
use App\Models\EstablecimientoPeriodoCiclo;
use Illuminate\Support\Collection;

class InformeAprendizaje
{
    public function __construct(
        private AsignacionesDocente $asignaciones,
        private LibretaAula $libreta,
        private DecimalesEstablecimiento $decimales,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function generar(
        EstablecimientoAula $aula,
        EstablecimientoPeriodoCiclo $ciclo,
        ?int $parcialNumero,
    ): array {
        $aula->loadMissing([
            'establecimiento',
            'periodo.ciclos',
            'establecimientoGrado.grado',
            'asignaciones.malla.asignatura',
            'asignaciones.malla.asignaturaPropia',
        ]);
        $establecimiento = $aula->establecimiento;
        $this->libreta->conEstablecimiento($establecimiento);
        $esParcial = $parcialNumero !== null;
        $esquema = $aula->periodo?->esquema_ciclo;

        $asignaturas = $aula->asignaciones
            ->map(fn (EstablecimientoAulaDocente $asignacion): ?EstablecimientoAsignatura => $asignacion->malla)
            ->filter()
            ->unique('id')
            ->filter(fn (EstablecimientoAsignatura $asignatura): bool => $asignatura->aparece_en_libreta)
            ->sortBy(fn (EstablecimientoAsignatura $asignatura): string => $asignatura->nombre(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $actividades = EstablecimientoInsumoActividad::query()
            ->with(['detalle', 'calificaciones'])
            ->where('establecimiento_aula_id', $aula->id)
            ->where('establecimiento_periodo_ciclo_id', $ciclo->id)
            ->when($esParcial, fn ($query) => $query->where('parcial_numero', $parcialNumero))
            ->orderBy('parcial_numero')
            ->orderBy('id')
            ->get()
            ->groupBy('establecimiento_asignatura_id');

        $cierres = $esParcial
            ? collect()
            : EstablecimientoCierreActividad::query()
                ->with('calificaciones.mejora')
                ->where('establecimiento_aula_id', $aula->id)
                ->where('establecimiento_periodo_ciclo_id', $ciclo->id)
                ->get()
                ->groupBy('establecimiento_asignatura_id');

        $tabla = $this->tablaEquivalencias((int) $aula->establecimiento_periodo_id);
        $asistencia = $this->asistenciaPorAlumno($aula, $ciclo, $parcialNumero);
        $comportamiento = $this->comportamientoPorAlumno($aula, $ciclo);
        $alumnos = $this->asignaciones->alumnosDelAula($aula);

        $estudiantes = $alumnos->map(function (Alumno $alumno) use (
            $aula,
            $ciclo,
            $asignaturas,
            $actividades,
            $cierres,
            $esParcial,
            $tabla,
            $asistencia,
            $comportamiento,
            $establecimiento,
        ): array {
            $notasNumericas = [];
            $filas = [];

            foreach ($asignaturas as $asignatura) {
                $delAlumno = $actividades->get($asignatura->id, collect());
                $cierresDe = $cierres->get($asignatura->id, collect());
                $cuantitativa = $this->esCuantitativa($aula, $asignatura);
                $nota = null;
                $textoCualitativo = null;

                if ($cuantitativa) {
                    $nota = $this->notaCuantitativa(
                        $aula,
                        $asignatura,
                        $ciclo,
                        $alumno,
                        $delAlumno,
                        $cierresDe,
                        $esParcial,
                    );

                    if ($nota !== null) {
                        $notasNumericas[] = $nota;
                    }
                } else {
                    $textoCualitativo = $this->notaCualitativaUnica($delAlumno, (int) $alumno->id);
                }

                $filas[] = [
                    'nombre' => $asignatura->nombre(),
                    'nota' => $cuantitativa
                        ? $this->libreta->textoNota($nota)
                        : ($textoCualitativo ?? '—'),
                    'cualitativa' => $cuantitativa && $asignatura->escala === EscalaCalificacion::Nacional
                        ? ($this->equivalenciaCualitativa($nota, $tabla) ?? '—')
                        : '—',
                ];
            }

            $promedio = $notasNumericas === []
                ? null
                : $this->decimales->aplicar(
                    array_sum($notasNumericas) / count($notasNumericas),
                    $establecimiento,
                );

            return [
                'nombre' => trim(($alumno->persona?->apellidos ?? '').' '.($alumno->persona?->nombres ?? '')),
                'identificacion' => (string) ($alumno->persona?->identificacion ?? ''),
                'asignaturas' => $filas,
                'promedio' => $this->libreta->textoNota($promedio),
                'promedio_cualitativa' => $this->equivalenciaCualitativa($promedio, $tabla) ?? '—',
                'asistencia' => $asistencia[(int) $alumno->id] ?? [
                    'faltas' => 0,
                    'justificadas' => 0,
                    'atrasos' => 0,
                ],
                'comportamiento' => $comportamiento[(int) $alumno->id] ?? '—',
            ];
        })->values()->all();

        return [
            'tipo' => $esParcial ? 'parcial' : 'ciclo',
            'titulo' => $esParcial
                ? 'Informe parcial de aprendizaje'
                : $this->tituloCiclo($esquema),
            'establecimiento' => $establecimiento?->nombre ?? '',
            'amie' => $establecimiento?->codigo_amie ?? '',
            'logo' => $establecimiento?->logoUrl(),
            'periodo' => $aula->periodo?->nombre ?? '',
            'aula' => $aula->etiqueta(),
            'ciclo' => $ciclo->nombre,
            'parcial' => $esParcial ? $this->etiquetaParcial((int) $parcialNumero) : null,
            'escala' => $this->escalaNacional(),
            'estudiantes' => $estudiantes,
            'base_legal' => 'Escala de calificaciones del Reglamento General a la Ley Orgánica de Educación Intercultural (Art. 194). Las notas en blanco no entran al promedio.',
        ];
    }

    /**
     * @param  Collection<int, EstablecimientoInsumoActividad>  $actividades
     * @param  Collection<int, EstablecimientoCierreActividad>  $cierres
     */
    private function notaCuantitativa(
        EstablecimientoAula $aula,
        EstablecimientoAsignatura $asignatura,
        EstablecimientoPeriodoCiclo $ciclo,
        Alumno $alumno,
        Collection $actividades,
        Collection $cierres,
        bool $esParcial,
    ): ?float {
        $insumos = $this->libreta->promedioInsumosAlCien($actividades, (int) $alumno->id);

        if ($esParcial) {
            return $insumos;
        }

        $pesos = $this->asignaciones->pesosEvaluacion($aula, $asignatura, $ciclo);

        return $this->libreta->promedioCiclo(
            $insumos,
            $this->libreta->cierreDe($cierres, TipoCierreEvaluacion::Examen, (int) $alumno->id, (int) $ciclo->id),
            $this->libreta->cierreDe($cierres, TipoCierreEvaluacion::Proyecto, (int) $alumno->id, (int) $ciclo->id),
            $pesos,
        );
    }

    /**
     * @param  Collection<int, EstablecimientoInsumoActividad>  $actividades
     */
    private function notaCualitativaUnica(Collection $actividades, int $alumnoId): ?string
    {
        $textos = $actividades
            ->map(fn (EstablecimientoInsumoActividad $actividad): string => $this->libreta->notaDe($actividad->calificaciones, $alumnoId))
            ->filter(fn (string $nota): bool => $nota !== '—')
            ->unique()
            ->values();

        if ($textos->count() !== 1) {
            return null;
        }

        return (string) $textos->first();
    }

    private function esCuantitativa(EstablecimientoAula $aula, EstablecimientoAsignatura $asignatura): bool
    {
        $tipo = $this->asignaciones->tipoInsumoAsignado((int) $aula->id, (int) $asignatura->id);

        return ($tipo?->tipo_calificacion ?? $asignatura->tipo_calificacion->value) === TipoCalificacion::Cuantitativa->value;
    }

    private function tablaEquivalencias(int $periodoId): ?Equivalencia
    {
        return Equivalencia::query()
            ->where('establecimiento_periodo_id', $periodoId)
            ->where('uso', UsoEquivalencia::Asignatura)
            ->where('activo', true)
            ->with(['detalles' => fn ($query) => $query->where('activo', true)->orderBy('minimo')->orderBy('id')])
            ->first();
    }

    private function equivalenciaCualitativa(?float $nota, ?Equivalencia $tabla): ?string
    {
        if ($nota === null) {
            return null;
        }

        if ($tabla instanceof Equivalencia) {
            foreach ($tabla->detalles as $detalle) {
                if ($detalle->minimo === null || $detalle->maximo === null) {
                    continue;
                }

                if ($nota >= (float) $detalle->minimo && $nota <= (float) $detalle->maximo) {
                    return (string) $detalle->nombre;
                }
            }
        }

        return match (true) {
            $nota >= 9.0 => 'Domina los aprendizajes requeridos',
            $nota >= 7.0 => 'Alcanza los aprendizajes requeridos',
            $nota > 4.0 => 'Está próximo a alcanzar los aprendizajes requeridos',
            default => 'No alcanza los aprendizajes requeridos',
        };
    }

    /**
     * @return array<int, array{faltas: int, justificadas: int, atrasos: int}>
     */
    private function asistenciaPorAlumno(
        EstablecimientoAula $aula,
        EstablecimientoPeriodoCiclo $ciclo,
        ?int $parcialNumero,
    ): array {
        $registros = EstablecimientoAsistencia::query()
            ->where('establecimiento_aula_id', $aula->id)
            ->where('establecimiento_periodo_ciclo_id', $ciclo->id)
            ->when($parcialNumero !== null, fn ($query) => $query->where('parcial_numero', $parcialNumero))
            ->get()
            ->groupBy('alumno_id');

        $resumen = [];

        foreach ($registros as $alumnoId => $filas) {
            $conteo = ['faltas' => 0, 'justificadas' => 0, 'atrasos' => 0];

            foreach ($filas as $fila) {
                match ($fila->estado) {
                    EstadoAsistencia::Falta => $conteo['faltas']++,
                    EstadoAsistencia::Justificada => $conteo['justificadas']++,
                    EstadoAsistencia::Atraso => $conteo['atrasos']++,
                    default => null,
                };
            }

            $resumen[(int) $alumnoId] = $conteo;
        }

        return $resumen;
    }

    /**
     * @return array<int, string>
     */
    private function comportamientoPorAlumno(EstablecimientoAula $aula, EstablecimientoPeriodoCiclo $ciclo): array
    {
        $registros = EstablecimientoComportamiento::query()
            ->with('detalle')
            ->where('establecimiento_aula_id', $aula->id)
            ->where('establecimiento_periodo_ciclo_id', $ciclo->id)
            ->get()
            ->groupBy('alumno_id');

        $resumen = [];

        foreach ($registros as $alumnoId => $filas) {
            $nombres = $filas
                ->map(fn (EstablecimientoComportamiento $registro): ?string => $registro->detalle?->nombre)
                ->filter()
                ->values();

            if ($nombres->isEmpty()) {
                continue;
            }

            $conteos = $nombres->countBy();
            $maximo = $conteos->max();
            $ganadores = $conteos
                ->filter(fn (int $cantidad): bool => $cantidad === $maximo)
                ->keys()
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            $resumen[(int) $alumnoId] = (string) $ganadores->first();
        }

        return $resumen;
    }

    /**
     * @return list<array{rango: string, etiqueta: string}>
     */
    private function escalaNacional(): array
    {
        return [
            ['rango' => '9,00 – 10,00', 'etiqueta' => 'Domina los aprendizajes requeridos'],
            ['rango' => '7,00 – 8,99', 'etiqueta' => 'Alcanza los aprendizajes requeridos'],
            ['rango' => '4,01 – 6,99', 'etiqueta' => 'Está próximo a alcanzar los aprendizajes requeridos'],
            ['rango' => '≤ 4,00', 'etiqueta' => 'No alcanza los aprendizajes requeridos'],
        ];
    }

    private function tituloCiclo(?EsquemaCiclo $esquema): string
    {
        return match ($esquema) {
            EsquemaCiclo::Trimestres => 'Informe trimestral de aprendizaje',
            default => 'Informe quimestral de aprendizaje',
        };
    }

    private function etiquetaParcial(int $numero): string
    {
        return match ($numero) {
            1 => '1er parcial',
            2 => '2do parcial',
            3 => '3er parcial',
            default => $numero.'º parcial',
        };
    }
}
