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
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoInsumoActividad;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\Sys_Grado;
use Illuminate\Support\Collection;

class InformeAprendizaje
{
    private const NOTA_MINIMA_PROMOCION = 7.0;

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
     * @return array<string, mixed>
     */
    public function generarFinal(EstablecimientoAula $aula): array
    {
        $consolidado = $this->consolidadoPeriodo($aula);

        return [
            ...$consolidado,
            'tipo' => 'final',
            'titulo' => 'Libreta final de calificaciones',
            'base_legal' => 'Escala de calificaciones del Reglamento General a la Ley Orgánica de Educación Intercultural (Art. 194). El promedio anual pondera los ciclos y, si aplica, el examen o proyecto final. Las notas en blanco no entran al promedio.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function generarCertificado(EstablecimientoAula $aula): array
    {
        $consolidado = $this->consolidadoPeriodo($aula);
        $gradoSiguiente = $this->gradoSiguiente($aula);

        $consolidado['estudiantes'] = array_map(
            function (array $estudiante) use ($gradoSiguiente): array {
                $promovido = (bool) $estudiante['promovido'];
                $estudiante['promocion'] = match (true) {
                    $promovido && $gradoSiguiente !== null => 'Es promovido/a a '.$gradoSiguiente.' de conformidad con el Art. 197 del Reglamento General a la LOEI.',
                    $promovido => 'Culminó el subnivel y reúne los requisitos de promoción previstos en el Art. 197 del Reglamento General a la LOEI.',
                    default => 'No reúne los requisitos de promoción previstos en el Art. 197 del Reglamento General a la LOEI (calificación mínima 7,00 en cada asignatura).',
                };

                return $estudiante;
            },
            $consolidado['estudiantes'],
        );

        return [
            ...$consolidado,
            'tipo' => 'certificado',
            'titulo' => 'Certificado de promoción',
            'grado_siguiente' => $gradoSiguiente,
            'representante' => $aula->establecimiento?->representante ?? '',
            'direccion' => $aula->establecimiento?->direccion ?? '',
            'fecha' => now()->format('d/m/Y'),
            'base_legal' => 'Arts. 194 y 197 del Reglamento General a la Ley Orgánica de Educación Intercultural. La promoción exige al menos 7,00 (Alcanza los aprendizajes requeridos) en cada asignatura cuantitativa.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function consolidadoPeriodo(EstablecimientoAula $aula): array
    {
        $aula->loadMissing([
            'establecimiento',
            'periodo.ciclos',
            'establecimientoGrado.grado.subnivel',
            'asignaciones.malla.asignatura',
            'asignaciones.malla.asignaturaPropia',
        ]);
        $establecimiento = $aula->establecimiento;
        $this->libreta->conEstablecimiento($establecimiento);
        $ciclos = $aula->periodo?->ciclos ?? collect();

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
            ->orderBy('parcial_numero')
            ->orderBy('id')
            ->get()
            ->groupBy('establecimiento_asignatura_id');

        $cierres = EstablecimientoCierreActividad::query()
            ->with('calificaciones.mejora')
            ->where('establecimiento_aula_id', $aula->id)
            ->get()
            ->groupBy('establecimiento_asignatura_id');

        $tabla = $this->tablaEquivalencias((int) $aula->establecimiento_periodo_id);
        $asistencia = $this->asistenciaPorAlumno($aula, null, null);
        $comportamiento = $this->comportamientoPorAlumno($aula, null);
        $alumnos = $this->asignaciones->alumnosDelAula($aula);
        $primerCiclo = $ciclos->first();
        $pesosPorAsignatura = [];
        $muestraExamenFinal = false;
        $muestraProyectoFinal = false;

        foreach ($asignaturas as $asignatura) {
            foreach ($ciclos as $ciclo) {
                $pesos = $this->asignaciones->pesosEvaluacion($aula, $asignatura, $ciclo);
                $pesosPorAsignatura[$asignatura->id][$ciclo->id] = $pesos;
                $muestraExamenFinal = $muestraExamenFinal || $pesos['examen_final'] > 0.009;
                $muestraProyectoFinal = $muestraProyectoFinal || $pesos['proyecto_final'] > 0.009;
            }
        }

        $estudiantes = $alumnos->map(function (Alumno $alumno) use (
            $aula,
            $ciclos,
            $asignaturas,
            $actividades,
            $cierres,
            $tabla,
            $asistencia,
            $comportamiento,
            $establecimiento,
            $primerCiclo,
            $pesosPorAsignatura,
        ): array {
            $notasNumericas = [];
            $filas = [];
            $promovido = true;
            $tieneNotaCuantitativa = false;

            foreach ($asignaturas as $asignatura) {
                $todas = $actividades->get($asignatura->id, collect());
                $cierresDe = $cierres->get($asignatura->id, collect());
                $cuantitativa = $this->esCuantitativa($aula, $asignatura);
                $notasCiclo = [];
                $promediosCiclo = [];
                $notaPeriodo = null;
                $textoCualitativo = null;
                $examenFinal = null;
                $proyectoFinal = null;
                $pesosFinales = $primerCiclo instanceof EstablecimientoPeriodoCiclo
                    ? ($pesosPorAsignatura[$asignatura->id][$primerCiclo->id] ?? [
                        'examen_final' => 0.0,
                        'proyecto_final' => 0.0,
                        'periodo' => 0.0,
                        'insumos' => 0.0,
                        'examen' => 0.0,
                        'proyecto' => 0.0,
                    ])
                    : [
                        'examen_final' => 0.0,
                        'proyecto_final' => 0.0,
                        'periodo' => 0.0,
                        'insumos' => 0.0,
                        'examen' => 0.0,
                        'proyecto' => 0.0,
                    ];

                foreach ($ciclos as $ciclo) {
                    $delCiclo = $todas
                        ->where('establecimiento_periodo_ciclo_id', $ciclo->id)
                        ->values();

                    if ($cuantitativa) {
                        $promedioCiclo = $this->notaCuantitativa(
                            $aula,
                            $asignatura,
                            $ciclo,
                            $alumno,
                            $delCiclo,
                            $cierresDe,
                            false,
                        );
                        $notasCiclo[] = [
                            'nombre' => $ciclo->nombre,
                            'nota' => $this->libreta->textoNota($promedioCiclo),
                        ];
                        $promediosCiclo[] = [
                            'valor' => $promedioCiclo,
                            'peso' => $pesosPorAsignatura[$asignatura->id][$ciclo->id]['periodo'] ?? 0.0,
                        ];
                    } else {
                        $texto = $this->notaCualitativaUnica($delCiclo, (int) $alumno->id);
                        $notasCiclo[] = [
                            'nombre' => $ciclo->nombre,
                            'nota' => $texto ?? '—',
                        ];
                    }
                }

                if ($cuantitativa) {
                    $examenFinal = $this->libreta->cierreDe($cierresDe, TipoCierreEvaluacion::ExamenFinal, (int) $alumno->id);
                    $proyectoFinal = $this->libreta->cierreDe($cierresDe, TipoCierreEvaluacion::ProyectoFinal, (int) $alumno->id);
                    $notaPeriodo = $this->libreta->promedioPeriodo(
                        $aula,
                        $promediosCiclo,
                        $examenFinal,
                        $proyectoFinal,
                        $pesosFinales,
                    );

                    if ($notaPeriodo !== null) {
                        $notasNumericas[] = $notaPeriodo;
                        $tieneNotaCuantitativa = true;

                        if ($asignatura->escala === EscalaCalificacion::Nacional && $notaPeriodo < self::NOTA_MINIMA_PROMOCION) {
                            $promovido = false;
                        }
                    }
                } else {
                    $textos = collect($notasCiclo)
                        ->pluck('nota')
                        ->filter(fn (string $nota): bool => $nota !== '—')
                        ->unique()
                        ->values();
                    $textoCualitativo = $textos->count() === 1 ? (string) $textos->first() : null;
                }

                $filas[] = [
                    'nombre' => $asignatura->nombre(),
                    'ciclos' => $notasCiclo,
                    'examen_final' => $this->libreta->textoNota($examenFinal),
                    'proyecto_final' => $this->libreta->textoNota($proyectoFinal),
                    'nota' => $cuantitativa
                        ? $this->libreta->textoNota($notaPeriodo)
                        : ($textoCualitativo ?? '—'),
                    'cualitativa' => $cuantitativa && $asignatura->escala === EscalaCalificacion::Nacional
                        ? ($this->equivalenciaCualitativa($notaPeriodo, $tabla) ?? '—')
                        : '—',
                ];
            }

            if (! $tieneNotaCuantitativa) {
                $promovido = false;
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
                'promovido' => $promovido,
            ];
        })->values()->all();

        return [
            'establecimiento' => $establecimiento?->nombre ?? '',
            'amie' => $establecimiento?->codigo_amie ?? '',
            'logo' => $establecimiento?->logoUrl(),
            'periodo' => $aula->periodo?->nombre ?? '',
            'aula' => $aula->etiqueta(),
            'grado' => $aula->establecimientoGrado?->etiqueta() ?? $aula->etiqueta(),
            'paralelo' => (string) $aula->paralelo,
            'ciclos' => $ciclos->map(fn (EstablecimientoPeriodoCiclo $ciclo): string => $ciclo->nombre)->values()->all(),
            'muestra_examen_final' => $muestraExamenFinal,
            'muestra_proyecto_final' => $muestraProyectoFinal,
            'escala' => $this->escalaNacional(),
            'estudiantes' => $estudiantes,
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
        ?EstablecimientoPeriodoCiclo $ciclo,
        ?int $parcialNumero,
    ): array {
        $registros = EstablecimientoAsistencia::query()
            ->where('establecimiento_aula_id', $aula->id)
            ->when(
                $ciclo instanceof EstablecimientoPeriodoCiclo,
                fn ($query) => $query->where('establecimiento_periodo_ciclo_id', $ciclo->id),
            )
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
    private function comportamientoPorAlumno(EstablecimientoAula $aula, ?EstablecimientoPeriodoCiclo $ciclo): array
    {
        $registros = EstablecimientoComportamiento::query()
            ->with('detalle')
            ->where('establecimiento_aula_id', $aula->id)
            ->when(
                $ciclo instanceof EstablecimientoPeriodoCiclo,
                fn ($query) => $query->where('establecimiento_periodo_ciclo_id', $ciclo->id),
            )
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

    private function gradoSiguiente(EstablecimientoAula $aula): ?string
    {
        $aula->loadMissing('establecimientoGrado.grado');
        $catalogo = $aula->establecimientoGrado?->grado;

        if (! $catalogo instanceof Sys_Grado) {
            return null;
        }

        $siguiente = Sys_Grado::query()
            ->where('subnivel_id', $catalogo->subnivel_id)
            ->where('id', '>', $catalogo->id)
            ->orderBy('id')
            ->first();

        if (! $siguiente instanceof Sys_Grado) {
            return null;
        }

        $local = EstablecimientoGrado::query()
            ->where('establecimiento_id', $aula->establecimiento_id)
            ->where('establecimiento_modalidad_jornada_id', $aula->establecimiento_modalidad_jornada_id)
            ->where('grado_id', $siguiente->id)
            ->first();

        return $local?->etiqueta() ?? $siguiente->nombre;
    }
}
