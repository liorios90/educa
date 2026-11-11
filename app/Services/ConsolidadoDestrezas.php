<?php

namespace App\Services;

use App\Enums\CalificacionDestreza;
use App\Models\Alumno;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoDestrezaCalificacion;
use App\Models\EstablecimientoIndicadorCalificacion;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\Sys_Destreza;
use Illuminate\Support\Collection;

/**
 * Resume los indicadores de cada destreza y, en Básica Elemental,
 * la nota sobre 10 equivalente para la libreta y la promoción.
 */
class ConsolidadoDestrezas
{
    /**
     * @param  Collection<int, EstablecimientoAsignatura>  $asignaturas
     * @param  Collection<int, EstablecimientoPeriodoCiclo>  $ciclos
     * @param  Collection<int, Alumno>  $alumnos
     * @return array<int, array<int, array{
     *     ciclos: array<int, array{letra: ?string, etiqueta: ?string, nota: ?float}>,
     *     anual: array{letra: ?string, etiqueta: ?string, nota: ?float},
     *     detalle: list<array{codigo: string, descripcion: string, ciclos: array<int, string>, anual: string}>
     * }>>
     */
    public function mapa(
        EstablecimientoAula $aula,
        Collection $asignaturas,
        Collection $ciclos,
        Collection $alumnos,
    ): array {
        $porDestrezas = $asignaturas
            ->filter(fn (EstablecimientoAsignatura $asignatura): bool => $asignatura->califica_por_destrezas)
            ->values();

        if ($porDestrezas->isEmpty() || $alumnos->isEmpty()) {
            return [];
        }

        $aula->loadMissing('establecimientoGrado.grado.subnivel');
        $subnivel = $aula->establecimientoGrado?->grado?->subnivel;
        $equivale = (bool) $subnivel?->equivale_nota_nacional;

        if ((bool) $subnivel?->usa_ambitos) {
            return $this->mapaPorDestreza($porDestrezas, $ciclos, $alumnos);
        }

        $catalogo = $this->catalogo($porDestrezas);
        $registros = EstablecimientoIndicadorCalificacion::query()
            ->where('establecimiento_aula_id', $aula->id)
            ->whereIn('establecimiento_asignatura_id', $porDestrezas->pluck('id'))
            ->get()
            ->groupBy(fn (EstablecimientoIndicadorCalificacion $registro): string => $registro->alumno_id.'-'.$registro->establecimiento_asignatura_id.'-'.$registro->establecimiento_periodo_ciclo_id.'-'.$registro->indicador_evaluacion_id);
        $mapa = [];

        foreach ($alumnos as $alumno) {
            foreach ($porDestrezas as $asignatura) {
                $destrezas = $catalogo->get($asignatura->asignatura_id.'-'.$asignatura->grado_id, collect());
                $detalle = [];
                $letrasCiclo = [];
                $notasCiclo = [];

                foreach ($ciclos as $ciclo) {
                    $letrasCiclo[(int) $ciclo->id] = [];
                    $notasCiclo[(int) $ciclo->id] = [];
                }

                foreach ($destrezas as $destreza) {
                    $letrasDestreza = [];
                    $celdas = [];

                    foreach ($ciclos as $ciclo) {
                        $letras = [];

                        foreach ($destreza->indicadores as $indicador) {
                            $clave = $alumno->id.'-'.$asignatura->id.'-'.$ciclo->id.'-'.$indicador->id;
                            $registro = $registros->get($clave)?->first();
                            $escala = $registro?->calificacion;

                            if ($escala instanceof CalificacionDestreza) {
                                $letras[] = $escala;
                            }
                        }

                        $consolidada = CalificacionDestreza::masBajo($letras);
                        $celdas[(int) $ciclo->id] = $consolidada?->value ?? '—';

                        if ($consolidada instanceof CalificacionDestreza) {
                            $letrasDestreza[] = $consolidada;
                            $letrasCiclo[(int) $ciclo->id][] = $consolidada;
                            $nota = $equivale ? $consolidada->notaElemental() : null;

                            if ($nota !== null) {
                                $notasCiclo[(int) $ciclo->id][] = $nota;
                            }
                        }
                    }

                    $anualDestreza = CalificacionDestreza::masBajo($letrasDestreza);
                    $detalle[] = [
                        'codigo' => $destreza->codigo,
                        'descripcion' => $destreza->descripcion,
                        'ciclos' => $celdas,
                        'anual' => $anualDestreza?->value ?? '—',
                    ];
                }

                $ciclosResumen = [];
                $letrasAnuales = [];
                $notasAnuales = [];

                foreach ($ciclos as $ciclo) {
                    $celda = $this->celda(
                        $letrasCiclo[(int) $ciclo->id] ?? [],
                        $notasCiclo[(int) $ciclo->id] ?? [],
                        $equivale,
                    );
                    $ciclosResumen[(int) $ciclo->id] = $celda;

                    if ($celda['letra'] !== null) {
                        $letrasAnuales[] = CalificacionDestreza::from($celda['letra']);
                    }

                    if ($celda['nota'] !== null) {
                        $notasAnuales[] = $celda['nota'];
                    }
                }

                $mapa[(int) $alumno->id][(int) $asignatura->id] = [
                    'ciclos' => $ciclosResumen,
                    'anual' => $this->celda($letrasAnuales, $notasAnuales, $equivale),
                    'detalle' => $detalle,
                ];
            }
        }

        return $mapa;
    }

    /**
     * @param  array<int, array{detalle: list<array{codigo: string, descripcion: string, ciclos: array<int, string>, anual: string}>}>  $porAsignatura
     * @param  Collection<int, EstablecimientoAsignatura>  $asignaturas
     * @param  Collection<int, EstablecimientoPeriodoCiclo>  $ciclos
     * @return list<array{asignatura: string, codigo: string, descripcion: string, periodos: list<array{nombre: string, nota: string}>, nota: string}>
     */
    public function filas(array $porAsignatura, Collection $asignaturas, Collection $ciclos): array
    {
        $filas = [];

        foreach ($asignaturas as $asignatura) {
            $resumen = $porAsignatura[(int) $asignatura->id] ?? null;

            if ($resumen === null) {
                continue;
            }

            foreach ($resumen['detalle'] as $destreza) {
                $periodos = [];

                foreach ($ciclos as $ciclo) {
                    $periodos[] = [
                        'nombre' => $ciclo->nombre,
                        'nota' => $destreza['ciclos'][(int) $ciclo->id] ?? '—',
                    ];
                }

                $filas[] = [
                    'asignatura' => $asignatura->nombre(),
                    'codigo' => $destreza['codigo'],
                    'descripcion' => $destreza['descripcion'],
                    'periodos' => $periodos,
                    'nota' => $destreza['anual'],
                ];
            }
        }

        return $filas;
    }

    /**
     * @param  Collection<int, EstablecimientoAsignatura>  $asignaturas
     * @return Collection<string, Collection<int, Sys_Destreza>>
     */
    private function catalogo(Collection $asignaturas): Collection
    {
        $pares = $asignaturas
            ->filter(fn (EstablecimientoAsignatura $asignatura): bool => $asignatura->asignatura_id !== null && $asignatura->grado_id !== null)
            ->values();

        if ($pares->isEmpty()) {
            return collect();
        }

        return Sys_Destreza::query()
            ->where('activo', true)
            ->where(function ($query) use ($pares): void {
                foreach ($pares as $asignatura) {
                    $query->orWhere(function ($par) use ($asignatura): void {
                        $par->where('asignatura_id', $asignatura->asignatura_id)
                            ->where('grado_id', $asignatura->grado_id);
                    });
                }
            })
            ->with([
                'criterio',
                'indicadores' => fn ($query) => $query->where('activo', true)->orderBy('orden')->orderBy('id'),
            ])
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Sys_Destreza $destreza): string => $destreza->asignatura_id.'-'.$destreza->grado_id);
    }

    /**
     * Inicial y Preparatoria no promedian las destrezas del ámbito.
     * Cada destreza conserva su letra y el resultado es el último registro evaluado.
     *
     * @param  Collection<int, EstablecimientoAsignatura>  $asignaturas
     * @param  Collection<int, EstablecimientoPeriodoCiclo>  $ciclos
     * @param  Collection<int, Alumno>  $alumnos
     * @return array<int, array<int, array{
     *     ciclos: array<int, array{letra: ?string, etiqueta: ?string, nota: ?float}>,
     *     anual: array{letra: ?string, etiqueta: ?string, nota: ?float},
     *     detalle: list<array{codigo: string, descripcion: string, ciclos: array<int, string>, anual: string}>
     * }>>
     */
    private function mapaPorDestreza(Collection $asignaturas, Collection $ciclos, Collection $alumnos): array
    {
        $catalogo = $this->catalogo($asignaturas);
        $registros = EstablecimientoDestrezaCalificacion::query()
            ->whereIn('establecimiento_asignatura_id', $asignaturas->pluck('id'))
            ->get()
            ->keyBy(fn (EstablecimientoDestrezaCalificacion $registro): string => $registro->alumno_id.'-'.$registro->establecimiento_asignatura_id.'-'.$registro->establecimiento_periodo_ciclo_id.'-'.$registro->destreza_id);
        $vacio = ['letra' => null, 'etiqueta' => null, 'nota' => null];
        $mapa = [];

        foreach ($alumnos as $alumno) {
            foreach ($asignaturas as $asignatura) {
                $destrezas = $catalogo->get($asignatura->asignatura_id.'-'.$asignatura->grado_id, collect());
                $detalle = [];

                foreach ($destrezas as $destreza) {
                    $celdas = [];
                    $letras = [];

                    foreach ($ciclos as $ciclo) {
                        $registro = $registros->get($alumno->id.'-'.$asignatura->id.'-'.$ciclo->id.'-'.$destreza->id);
                        $escala = $registro?->calificacion;
                        $celdas[(int) $ciclo->id] = $escala instanceof CalificacionDestreza ? $escala->value : '—';

                        if ($escala instanceof CalificacionDestreza) {
                            $letras[] = $escala;
                        }
                    }

                    $anual = CalificacionDestreza::ultimaEvaluada($letras);
                    $detalle[] = [
                        'codigo' => $destreza->codigo,
                        'descripcion' => $destreza->descripcion,
                        'ciclos' => $celdas,
                        'anual' => $anual?->value ?? '—',
                    ];
                }

                $mapa[(int) $alumno->id][(int) $asignatura->id] = [
                    'ciclos' => $ciclos->mapWithKeys(fn (EstablecimientoPeriodoCiclo $ciclo): array => [(int) $ciclo->id => $vacio])->all(),
                    'anual' => $vacio,
                    'detalle' => $detalle,
                ];
            }
        }

        return $mapa;
    }

    /**
     * @param  list<CalificacionDestreza>  $letras
     * @param  list<float>  $notas
     * @return array{letra: ?string, etiqueta: ?string, nota: ?float}
     */
    private function celda(array $letras, array $notas, bool $equivale): array
    {
        $nota = $equivale && $notas !== []
            ? array_sum($notas) / count($notas)
            : null;
        $escala = $nota !== null
            ? CalificacionDestreza::desdeNotaElemental($nota)
            : CalificacionDestreza::masBajo($letras);

        return [
            'letra' => $escala?->value,
            'etiqueta' => $escala?->etiqueta(),
            'nota' => $nota,
        ];
    }
}
