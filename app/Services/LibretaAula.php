<?php

namespace App\Services;

use App\Enums\EsquemaCiclo;
use App\Enums\ModoPonderacionCiclos;
use App\Enums\TipoCalificacion;
use App\Enums\TipoCierreEvaluacion;
use App\Models\Alumno;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoCierreActividad;
use App\Models\EstablecimientoInsumoActividad;
use App\Models\EstablecimientoPeriodo;
use App\Models\EstablecimientoPeriodoCiclo;
use Illuminate\Support\Collection;

class LibretaAula
{
    public function __construct(private AsignacionesDocente $asignaciones) {}

    /**
     * @return Collection<int, EstablecimientoAula>
     */
    public function aulasDelPeriodo(EstablecimientoPeriodo $periodo): Collection
    {
        return EstablecimientoAula::query()
            ->with(['establecimientoGrado.grado'])
            ->where('establecimiento_id', $periodo->establecimiento_id)
            ->where('establecimiento_periodo_id', $periodo->id)
            ->where('establecimiento_modalidad_jornada_id', $periodo->establecimiento_modalidad_jornada_id)
            ->orderBy('establecimiento_grado_id')
            ->orderBy('paralelo')
            ->get()
            ->sortBy(fn (EstablecimientoAula $aula): string => $aula->etiqueta(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @return array{
     *     ciclo_label: string,
     *     materias: list<array<string, mixed>>
     * }
     */
    public function paraAula(EstablecimientoAula $aula): array
    {
        $aula->loadMissing(['periodo.ciclos', 'establecimientoGrado.grado', 'asignaciones.malla.asignatura', 'asignaciones.malla.asignaturaPropia']);
        $periodo = $aula->periodo;
        $ciclos = $periodo?->ciclos ?? collect();

        $actividades = EstablecimientoInsumoActividad::query()
            ->with(['detalle', 'calificaciones'])
            ->where('establecimiento_aula_id', $aula->id)
            ->orderBy('parcial_numero')
            ->orderBy('id')
            ->get()
            ->groupBy('establecimiento_asignatura_id');

        $cierres = EstablecimientoCierreActividad::query()
            ->with('calificaciones')
            ->where('establecimiento_aula_id', $aula->id)
            ->get()
            ->groupBy('establecimiento_asignatura_id');

        $materias = $aula->asignaciones
            ->map(fn (EstablecimientoAulaDocente $asignacion): ?EstablecimientoAsignatura => $asignacion->malla)
            ->filter()
            ->unique('id')
            ->sortBy(fn (EstablecimientoAsignatura $asignatura): string => $asignatura->nombre(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(fn (EstablecimientoAsignatura $asignatura): array => $this->materia(
                $aula,
                $asignatura,
                $ciclos,
                $actividades->get($asignatura->id, collect()),
                $cierres->get($asignatura->id, collect()),
            ))
            ->all();

        return [
            'ciclo_label' => $this->etiquetaCiclos($periodo?->esquema_ciclo),
            'materias' => $materias,
        ];
    }

    /**
     * @param  Collection<int, EstablecimientoPeriodoCiclo>  $ciclos
     * @param  Collection<int, EstablecimientoInsumoActividad>  $actividades
     * @param  Collection<int, EstablecimientoCierreActividad>  $cierres
     * @return array<string, mixed>
     */
    private function materia(
        EstablecimientoAula $aula,
        EstablecimientoAsignatura $asignatura,
        Collection $ciclos,
        Collection $actividades,
        Collection $cierres,
    ): array {
        $alumnos = $this->asignaciones->alumnosDe($aula, $asignatura);
        $tipo = $this->asignaciones->tipoInsumoAsignado((int) $aula->id, (int) $asignatura->id);
        $cuantitativa = ($tipo?->tipo_calificacion ?? $asignatura->tipo_calificacion->value) === TipoCalificacion::Cuantitativa->value;
        $primerCiclo = $ciclos->first();
        $pesosFinales = $primerCiclo instanceof EstablecimientoPeriodoCiclo
            ? $this->asignaciones->pesosEvaluacion($aula, $asignatura, $primerCiclo)
            : ['examen_final' => 0.0, 'proyecto_final' => 0.0];

        $grupos = [];
        $columnas = [];
        $pesosPorCiclo = [];

        foreach ($ciclos as $ciclo) {
            $pesos = $this->asignaciones->pesosEvaluacion($aula, $asignatura, $ciclo);
            $pesosPorCiclo[$ciclo->id] = $pesos;
            $columnasCiclo = [];

            if ($pesos['insumos'] > 0.009) {
                $columnasCiclo[] = [
                    'clave' => 'insumos-'.$ciclo->id,
                    'etiqueta' => 'Insumos',
                    'ayuda' => $this->porcentaje($pesos['insumos']),
                    'tipo' => 'insumos',
                ];
            }

            if ($pesos['examen'] > 0.009) {
                $columnasCiclo[] = [
                    'clave' => 'examen-'.$ciclo->id,
                    'etiqueta' => 'Examen',
                    'ayuda' => $this->porcentaje($pesos['examen']),
                ];
            }

            if ($pesos['proyecto'] > 0.009) {
                $columnasCiclo[] = [
                    'clave' => 'proyecto-'.$ciclo->id,
                    'etiqueta' => 'Proyecto',
                    'ayuda' => $this->porcentaje($pesos['proyecto']),
                ];
            }

            $columnasCiclo[] = [
                'clave' => 'promedio-ciclo-'.$ciclo->id,
                'etiqueta' => 'Prom.',
                'ayuda' => $this->porcentaje($pesos['periodo']),
            ];

            $grupos[] = [
                'clave' => 'ciclo-'.$ciclo->id,
                'etiqueta' => $ciclo->nombre,
                'columnas' => count($columnasCiclo),
            ];
            array_push($columnas, ...$columnasCiclo);
        }

        $columnasFinales = [];

        if ($pesosFinales['examen_final'] > 0.009) {
            $columnasFinales[] = [
                'clave' => 'examen-final',
                'etiqueta' => 'Examen final',
                'ayuda' => $this->porcentaje($pesosFinales['examen_final']),
            ];
        }

        if ($pesosFinales['proyecto_final'] > 0.009) {
            $columnasFinales[] = [
                'clave' => 'proyecto-final',
                'etiqueta' => 'Proyecto final',
                'ayuda' => $this->porcentaje($pesosFinales['proyecto_final']),
            ];
        }

        if ($columnasFinales !== []) {
            $grupos[] = [
                'clave' => 'finales',
                'etiqueta' => 'Finales',
                'columnas' => count($columnasFinales),
            ];
            array_push($columnas, ...$columnasFinales);
        }

        $grupos[] = [
            'clave' => 'promedio',
            'etiqueta' => 'Periodo',
            'columnas' => 1,
        ];
        $columnas[] = [
            'clave' => 'promedio',
            'etiqueta' => 'Promedio',
            'ayuda' => '',
        ];

        $filas = $alumnos->map(function (Alumno $alumno) use ($aula, $asignatura, $ciclos, $actividades, $cierres, $cuantitativa, $pesosPorCiclo, $pesosFinales, $columnas): array {
            $celdas = [];
            $anclas = [];
            $promediosCiclo = [];
            $nombreAlumno = trim(($alumno->persona->apellidos ?? '').' '.($alumno->persona->nombres ?? ''));

            foreach ($ciclos as $ciclo) {
                $pesos = $pesosPorCiclo[$ciclo->id];
                $delCiclo = $actividades->where('establecimiento_periodo_ciclo_id', $ciclo->id)->values();

                $insumos = $cuantitativa
                    ? $this->promedioInsumos($delCiclo, (int) $alumno->id)
                    : null;

                if ($pesos['insumos'] > 0.009) {
                    $clave = 'insumos-'.$ciclo->id;
                    $celdas[$clave] = $this->textoNota($insumos);
                    $detalle = $this->detalleInsumos($delCiclo, (int) $alumno->id);

                    if ($detalle !== []) {
                        $anclas[$clave] = [
                            'id' => 'insumos-'.$asignatura->id.'-'.$ciclo->id.'-'.$alumno->id,
                            'titulo' => $nombreAlumno.' · '.$asignatura->nombre().' · '.$ciclo->nombre,
                            'grupos' => $detalle,
                        ];
                    }
                }

                $examen = $this->cierreDe($cierres, TipoCierreEvaluacion::Examen, (int) $alumno->id, (int) $ciclo->id);
                $proyecto = $this->cierreDe($cierres, TipoCierreEvaluacion::Proyecto, (int) $alumno->id, (int) $ciclo->id);

                if ($pesos['examen'] > 0.009) {
                    $celdas['examen-'.$ciclo->id] = $this->textoNota($examen);
                }

                if ($pesos['proyecto'] > 0.009) {
                    $celdas['proyecto-'.$ciclo->id] = $this->textoNota($proyecto);
                }

                $promedioCiclo = $cuantitativa
                    ? $this->promedioCiclo($insumos, $examen, $proyecto, $pesos)
                    : null;
                $celdas['promedio-ciclo-'.$ciclo->id] = $this->textoNota($promedioCiclo);
                $promediosCiclo[] = [
                    'valor' => $promedioCiclo,
                    'peso' => $pesos['periodo'],
                ];
            }

            $examenFinal = $this->cierreDe($cierres, TipoCierreEvaluacion::ExamenFinal, (int) $alumno->id);
            $proyectoFinal = $this->cierreDe($cierres, TipoCierreEvaluacion::ProyectoFinal, (int) $alumno->id);

            if ($pesosFinales['examen_final'] > 0.009) {
                $celdas['examen-final'] = $this->textoNota($examenFinal);
            }

            if ($pesosFinales['proyecto_final'] > 0.009) {
                $celdas['proyecto-final'] = $this->textoNota($proyectoFinal);
            }

            $celdas['promedio'] = $cuantitativa
                ? $this->textoNota($this->promedioPeriodo(
                    $aula,
                    $promediosCiclo,
                    $examenFinal,
                    $proyectoFinal,
                    $pesosFinales,
                ))
                : '—';

            foreach ($columnas as $columna) {
                $celdas[$columna['clave']] ??= '—';
            }

            return [
                'alumno' => $nombreAlumno,
                'celdas' => $celdas,
                'anclas' => $anclas,
            ];
        })->all();

        return [
            'id' => (int) $asignatura->id,
            'nombre' => $asignatura->nombre(),
            'cuantitativa' => $cuantitativa,
            'grupos' => $grupos,
            'columnas' => $columnas,
            'filas' => $filas,
        ];
    }

    /**
     * @param  array{insumos: float, examen: float, proyecto: float, periodo: float, examen_final: float, proyecto_final: float}  $pesos
     */
    private function promedioCiclo(
        ?float $insumos,
        ?float $examen,
        ?float $proyecto,
        array $pesos,
    ): ?float {
        $partes = [];

        if ($pesos['insumos'] > 0.009 && $insumos !== null) {
            $partes[] = ['valor' => $insumos, 'peso' => $pesos['insumos']];
        }

        if ($pesos['examen'] > 0.009 && $examen !== null) {
            $partes[] = ['valor' => $examen, 'peso' => $pesos['examen']];
        }

        if ($pesos['proyecto'] > 0.009 && $proyecto !== null) {
            $partes[] = ['valor' => $proyecto, 'peso' => $pesos['proyecto']];
        }

        return $this->ponderar($partes);
    }

    /**
     * @param  list<array{valor: ?float, peso: float}>  $promediosCiclo
     * @param  array{insumos: float, examen: float, proyecto: float, periodo: float, examen_final: float, proyecto_final: float}  $pesosFinales
     */
    private function promedioPeriodo(
        EstablecimientoAula $aula,
        array $promediosCiclo,
        ?float $examenFinal,
        ?float $proyectoFinal,
        array $pesosFinales,
    ): ?float {
        $aula->loadMissing('periodo');
        $modo = $aula->periodo?->modo_ponderacion_ciclos ?? ModoPonderacionCiclos::Individual;
        $partes = [];

        if ($modo === ModoPonderacionCiclos::Promedio) {
            $valores = array_values(array_filter(
                array_map(fn (array $ciclo): ?float => $ciclo['valor'], $promediosCiclo),
                fn (?float $valor): bool => $valor !== null,
            ));

            if ($valores !== []) {
                $partes[] = [
                    'valor' => array_sum($valores) / count($valores),
                    'peso' => (float) ($aula->periodo?->porcentaje_promedio_ciclos ?? 0),
                ];
            }
        } else {
            foreach ($promediosCiclo as $ciclo) {
                if ($ciclo['valor'] !== null && $ciclo['peso'] > 0.009) {
                    $partes[] = ['valor' => $ciclo['valor'], 'peso' => $ciclo['peso']];
                }
            }
        }

        if ($pesosFinales['examen_final'] > 0.009 && $examenFinal !== null) {
            $partes[] = ['valor' => $examenFinal, 'peso' => $pesosFinales['examen_final']];
        }

        if ($pesosFinales['proyecto_final'] > 0.009 && $proyectoFinal !== null) {
            $partes[] = ['valor' => $proyectoFinal, 'peso' => $pesosFinales['proyecto_final']];
        }

        return $this->ponderar($partes);
    }

    /**
     * @param  Collection<int, EstablecimientoInsumoActividad>  $actividades
     * @return list<array{nombre: string, porcentaje: string, promedio: string, actividades: list<array{id: int, nombre: string, parcial: string, nota: string}>}>
     */
    private function detalleInsumos(Collection $actividades, int $alumnoId): array
    {
        return $actividades
            ->groupBy('tipo_insumo_detalle_id')
            ->map(function (Collection $grupo) use ($alumnoId): array {
                /** @var EstablecimientoInsumoActividad $primera */
                $primera = $grupo->first();
                $detalle = $primera->detalle;
                $notas = $grupo
                    ->map(fn (EstablecimientoInsumoActividad $actividad): ?float => $this->notaNumerica($actividad->calificaciones, $alumnoId))
                    ->filter(fn (?float $nota): bool => $nota !== null)
                    ->values();
                $promedio = $notas->isEmpty()
                    ? null
                    : $notas->sum() / $notas->count();

                return [
                    'nombre' => $detalle?->nombre ?? 'Insumo',
                    'porcentaje' => $detalle?->porcentaje === null ? '' : $this->porcentaje((float) $detalle->porcentaje),
                    'promedio' => $this->textoNota($promedio),
                    'actividades' => $grupo
                        ->map(fn (EstablecimientoInsumoActividad $actividad): array => [
                            'id' => (int) $actividad->id,
                            'nombre' => $actividad->nombre,
                            'parcial' => $actividad->parcial_numero > 0 ? $this->etiquetaParcial((int) $actividad->parcial_numero) : '',
                            'nota' => $this->notaDe($actividad->calificaciones, $alumnoId),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, EstablecimientoInsumoActividad>  $actividades
     */
    private function promedioInsumos(Collection $actividades, int $alumnoId): ?float
    {
        $porDetalle = $actividades->groupBy('tipo_insumo_detalle_id');
        $aportes = [];
        $simples = [];

        foreach ($porDetalle as $grupo) {
            $notas = $grupo
                ->map(fn (EstablecimientoInsumoActividad $actividad): ?float => $this->notaNumerica($actividad->calificaciones, $alumnoId))
                ->filter(fn (?float $nota): bool => $nota !== null)
                ->values();

            if ($notas->isEmpty()) {
                continue;
            }

            $promedio = $notas->sum() / $notas->count();
            $simples[] = $promedio;
            /** @var EstablecimientoInsumoActividad $primera */
            $primera = $grupo->first();
            $peso = (float) ($primera->detalle?->porcentaje ?? 0);

            if ($peso > 0.009) {
                $aportes[] = $promedio * ($peso / 100);
            }
        }

        if ($aportes !== []) {
            return round(array_sum($aportes), 2);
        }

        if ($simples === []) {
            return null;
        }

        return round(array_sum($simples) / count($simples), 2);
    }

    /**
     * @param  list<array{valor: float, peso: float}>  $partes
     */
    private function ponderar(array $partes): ?float
    {
        $peso = array_sum(array_column($partes, 'peso'));

        if ($partes === [] || $peso <= 0.009) {
            return null;
        }

        $suma = 0.0;

        foreach ($partes as $parte) {
            $suma += $parte['valor'] * $parte['peso'];
        }

        return round($suma / $peso, 2);
    }

    /**
     * @param  Collection<int, EstablecimientoCierreActividad>  $cierres
     */
    private function cierreDe(
        Collection $cierres,
        TipoCierreEvaluacion $tipo,
        int $alumnoId,
        ?int $cicloId = null,
    ): ?float {
        $actividad = $cierres->first(function (EstablecimientoCierreActividad $cierre) use ($tipo, $cicloId): bool {
            if ($cierre->tipo !== $tipo) {
                return false;
            }

            if ($tipo->esFinal() || $cicloId === null) {
                return true;
            }

            return (int) $cierre->establecimiento_periodo_ciclo_id === $cicloId;
        });

        if ($actividad === null) {
            return null;
        }

        return $this->notaNumerica($actividad->calificaciones, $alumnoId);
    }

    private function notaDe(Collection $calificaciones, int $alumnoId): string
    {
        $calificacion = $calificaciones->firstWhere('alumno_id', $alumnoId);

        if ($calificacion === null || trim((string) $calificacion->nota) === '') {
            return '—';
        }

        return (string) $calificacion->nota;
    }

    private function notaNumerica(Collection $calificaciones, int $alumnoId): ?float
    {
        $texto = $this->notaDe($calificaciones, $alumnoId);

        if ($texto === '—' || ! is_numeric($texto)) {
            return null;
        }

        return round((float) $texto, 2);
    }

    private function textoNota(?float $nota): string
    {
        if ($nota === null) {
            return '—';
        }

        return number_format($nota, 2, '.', '');
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

    private function porcentaje(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.').' %';
    }

    private function etiquetaCiclos(?EsquemaCiclo $esquema): string
    {
        return match ($esquema) {
            EsquemaCiclo::Quimestres => 'Quimestres',
            EsquemaCiclo::Trimestres => 'Trimestres',
            default => 'Ciclos',
        };
    }
}
