<?php

namespace App\Http\Controllers\Admin;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateConfiguracionAsignaturasAulaRequest;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaAsignaturaCiclo;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoPeriodo;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\EstablecimientoPeriodoConfiguracionGrado;
use App\Models\EstablecimientoPeriodoConfiguracionGradoCiclo;
use App\Services\GradosConPonderacionCuantitativa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfiguracionAsignaturasController extends Controller
{
    public function __construct(
        private ActiveOferta $activeOferta,
        private ActivePeriodo $activePeriodo,
        private GradosConPonderacionCuantitativa $gradosCuantitativos,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $establecimientoId = $user?->establecimiento_id;

        abort_if($establecimientoId === null, 403);

        $establecimiento = Establecimiento::query()->findOrFail($establecimientoId);

        if ($this->activeOferta->needsSelection($user)) {
            return redirect()->guest(route('oferta.select'));
        }

        $oferta = $this->activeOferta->get($user);
        $periodo = $this->activePeriodo->get($user);
        $gradosCuantitativos = $this->gradosCuantitativos->paraOferta($oferta?->id);
        $ciclosPeriodo = $periodo?->ciclos ?? collect();

        $grados = $oferta === null
            ? collect()
            : EstablecimientoGrado::query()
                ->with('grado')
                ->where('establecimiento_id', $establecimiento->id)
                ->where('establecimiento_modalidad_jornada_id', $oferta->id)
                ->whereIn('grado_id', $gradosCuantitativos->keys())
                ->get()
                ->sortBy(fn (EstablecimientoGrado $grado): string => $grado->etiqueta(), SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

        $aulasPorGrado = $periodo === null || $grados->isEmpty()
            ? collect()
            : EstablecimientoAula::query()
                ->with('establecimientoGrado.grado')
                ->where('establecimiento_id', $establecimiento->id)
                ->where('establecimiento_modalidad_jornada_id', $oferta->id)
                ->where('establecimiento_periodo_id', $periodo->id)
                ->whereIn('establecimiento_grado_id', $grados->pluck('id'))
                ->get()
                ->groupBy('establecimiento_grado_id');

        $configuracionesPorAula = $periodo === null || $aulasPorGrado->isEmpty()
            ? collect()
            : EstablecimientoAulaAsignaturaCiclo::query()
                ->where('establecimiento_periodo_id', $periodo->id)
                ->whereIn('establecimiento_aula_id', $aulasPorGrado->flatten()->pluck('id'))
                ->get()
                ->groupBy('establecimiento_aula_id')
                ->map(fn ($configuraciones): Collection => $configuraciones->groupBy('establecimiento_asignatura_id'));

        $ciclosPorGradoCatalogo = $this->ciclosPorGradoCatalogo($periodo);

        $aulas = $periodo === null
            ? collect()
            : $grados->flatMap(function (EstablecimientoGrado $grado) use ($aulasPorGrado, $ciclosPeriodo, $gradosCuantitativos, $configuracionesPorAula, $ciclosPorGradoCatalogo, $periodo): array {
                $aulasDelGrado = $aulasPorGrado->get($grado->id, collect());
                $ciclosGrado = $ciclosPorGradoCatalogo->get((int) $grado->grado_id, collect());

                if ($aulasDelGrado->isEmpty()) {
                    return [[
                        'etiqueta' => $grado->etiqueta(),
                        'grado_id' => (int) $grado->grado_id,
                        'aula_id' => null,
                        'sin_aula' => true,
                        'puede_configurar' => ! $periodo->estaAbierto(),
                        'asignaturas' => $this->asignaturasParaAula(
                            $gradosCuantitativos->get($grado->grado_id)['asignaturas'] ?? collect(),
                            $ciclosPeriodo,
                            collect(),
                            $ciclosGrado,
                        ),
                    ]];
                }

                return $aulasDelGrado
                    ->map(function (EstablecimientoAula $aula) use ($grado, $ciclosPeriodo, $gradosCuantitativos, $configuracionesPorAula, $ciclosGrado, $periodo): array {
                        return [
                            'etiqueta' => $aula->etiqueta(),
                            'grado_id' => (int) $grado->grado_id,
                            'aula_id' => (int) $aula->id,
                            'sin_aula' => false,
                            'puede_configurar' => ! $periodo->estaAbierto(),
                            'asignaturas' => $this->asignaturasParaAula(
                                $gradosCuantitativos->get($grado->grado_id)['asignaturas'] ?? collect(),
                                $ciclosPeriodo,
                                $configuracionesPorAula->get($aula->id, collect()),
                                $ciclosGrado,
                            ),
                        ];
                    })
                    ->all();
            })
                ->sortBy('etiqueta', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

        return view('admin.configuracion-asignaturas.index', [
            'establecimiento' => $establecimiento,
            'oferta' => $oferta,
            'periodo' => $periodo,
            'aulas' => $aulas,
        ]);
    }

    public function update(UpdateConfiguracionAsignaturasAulaRequest $request, EstablecimientoAula $aula): RedirectResponse
    {
        $user = $request->user();
        $establecimientoId = $user?->establecimiento_id;
        $oferta = $this->activeOferta->get($user);
        $periodo = $this->activePeriodo->get($user);

        abort_if($establecimientoId === null || $oferta === null || $periodo === null, 403);
        abort_unless(
            (int) $aula->establecimiento_id === (int) $establecimientoId
            && (int) $aula->establecimiento_modalidad_jornada_id === (int) $oferta->id
            && (int) $aula->establecimiento_periodo_id === (int) $periodo->id,
            404,
        );
        abort_if($periodo->estaAbierto(), 403, 'No se puede cambiar la configuración de un periodo aperturado.');

        $aula->load('establecimientoGrado');
        $asignaturasPermitidas = $this->gradosCuantitativos
            ->paraOferta($oferta->id)
            ->get($aula->establecimientoGrado?->grado_id)['asignaturas']
            ?? collect();
        $idsPermitidos = $asignaturasPermitidas->pluck('id')->map(strval(...))->all();
        $configuraciones = $request->validated('configuraciones');
        $ordenesCiclo = $periodo->ciclos->pluck('orden')->map(fn (int $orden): string => (string) $orden)->all();

        foreach ($configuraciones as $asignaturaId => $configuracion) {
            if (! in_array((string) $asignaturaId, $idsPermitidos, true)) {
                throw ValidationException::withMessages([
                    'configuraciones' => 'Solo puedes configurar asignaturas cuantitativas de esta aula.',
                ]);
            }

            if (! filter_var($configuracion['personalizada'], FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            $ordenesEnviadas = array_map('strval', array_keys($configuracion['ciclos'] ?? []));
            sort($ordenesEnviadas);
            $ordenesEsperadas = $ordenesCiclo;
            sort($ordenesEsperadas);

            if ($ordenesEnviadas !== $ordenesEsperadas) {
                throw ValidationException::withMessages([
                    'configuraciones' => 'Configura todos los ciclos del periodo para cada asignatura personalizada.',
                ]);
            }
        }

        DB::transaction(function () use ($aula, $periodo, $configuraciones, $idsPermitidos): void {
            EstablecimientoAulaAsignaturaCiclo::query()
                ->where('establecimiento_periodo_id', $periodo->id)
                ->where('establecimiento_aula_id', $aula->id)
                ->whereIn('establecimiento_asignatura_id', $idsPermitidos)
                ->delete();

            foreach ($configuraciones as $asignaturaId => $configuracion) {
                if (! filter_var($configuracion['personalizada'], FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }

                foreach ($configuracion['ciclos'] as $orden => $ciclo) {
                    EstablecimientoAulaAsignaturaCiclo::query()->create([
                        'establecimiento_periodo_id' => $periodo->id,
                        'establecimiento_aula_id' => $aula->id,
                        'establecimiento_asignatura_id' => (int) $asignaturaId,
                        'ciclo_orden' => (int) $orden,
                        'porcentaje_insumos' => number_format((float) $ciclo['porcentaje_insumos'], 2, '.', ''),
                        'porcentaje_examen' => number_format((float) $ciclo['porcentaje_examen'], 2, '.', ''),
                        'porcentaje_proyecto' => number_format((float) $ciclo['porcentaje_proyecto'], 2, '.', ''),
                    ]);
                }
            }
        });

        return redirect()
            ->route('Admin.configuracion-asignaturas.index')
            ->with('status', 'configuracion-asignaturas-updated');
    }

    /**
     * @return Collection<int, Collection<int, EstablecimientoPeriodoConfiguracionGradoCiclo>>
     */
    private function ciclosPorGradoCatalogo(?EstablecimientoPeriodo $periodo): Collection
    {
        if ($periodo === null) {
            return collect();
        }

        $ciclosPorGrado = collect();

        $configuraciones = EstablecimientoPeriodoConfiguracionGrado::query()
            ->with(['grados', 'ciclos'])
            ->where('establecimiento_periodo_id', $periodo->id)
            ->get();

        foreach ($configuraciones as $configuracion) {
            $ciclos = $configuracion->ciclos->keyBy('ciclo_orden');

            foreach ($configuracion->grados as $grado) {
                $ciclosPorGrado->put((int) $grado->id, $ciclos);
            }
        }

        return $ciclosPorGrado;
    }

    /**
     * @param  Collection<int, EstablecimientoAsignatura>  $asignaturas
     * @param  Collection<int, EstablecimientoPeriodoCiclo>  $ciclos
     * @param  Collection<int, Collection<int, EstablecimientoAulaAsignaturaCiclo>>  $configuraciones
     * @param  Collection<int, EstablecimientoPeriodoConfiguracionGradoCiclo>  $ciclosGrado
     * @return list<array{id: int, nombre: string, personalizada: bool, ciclos: list<array{orden: int, nombre: string, porcentaje_insumos: string, porcentaje_examen: string, porcentaje_proyecto: string}>}>
     */
    private function asignaturasParaAula(
        Collection $asignaturas,
        Collection $ciclos,
        Collection $configuraciones,
        Collection $ciclosGrado,
    ): array {
        return $asignaturas->map(function (EstablecimientoAsignatura $asignatura) use ($ciclos, $configuraciones, $ciclosGrado): array {
            $guardados = $configuraciones->get($asignatura->id, collect())->keyBy('ciclo_orden');

            return [
                'id' => (int) $asignatura->id,
                'nombre' => $asignatura->asignaturaPropia?->nombre ?? $asignatura->asignatura?->nombre ?? 'Asignatura',
                'personalizada' => $guardados->isNotEmpty(),
                'ciclos' => $ciclos->map(function (EstablecimientoPeriodoCiclo $ciclo) use ($guardados, $ciclosGrado): array {
                    $guardado = $guardados->get($ciclo->orden);
                    $porGrado = $ciclosGrado->get($ciclo->orden);

                    return [
                        'orden' => (int) $ciclo->orden,
                        'nombre' => $ciclo->nombre,
                        'porcentaje_insumos' => (string) ($guardado?->porcentaje_insumos ?? $porGrado?->porcentaje_insumos ?? $ciclo->porcentaje_insumos),
                        'porcentaje_examen' => (string) ($guardado?->porcentaje_examen ?? $porGrado?->porcentaje_examen ?? $ciclo->porcentaje_examen),
                        'porcentaje_proyecto' => (string) ($guardado?->porcentaje_proyecto ?? $porGrado?->porcentaje_proyecto ?? $ciclo->porcentaje_proyecto),
                    ];
                })->all(),
            ];
        })->values()->all();
    }
}
