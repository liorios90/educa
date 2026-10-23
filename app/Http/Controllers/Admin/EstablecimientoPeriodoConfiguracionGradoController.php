<?php

namespace App\Http\Controllers\Admin;

use App\Auth\ActivePeriodo;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEstablecimientoPeriodoConfiguracionGradoRequest;
use App\Models\EstablecimientoPeriodo;
use App\Models\EstablecimientoPeriodoConfiguracionGrado;
use App\Services\GradosConPonderacionCuantitativa;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EstablecimientoPeriodoConfiguracionGradoController extends Controller
{
    public function __construct(
        private ActivePeriodo $activePeriodo,
        private GradosConPonderacionCuantitativa $gradosCuantitativos,
    ) {}

    public function index(): View
    {
        $periodo = $this->activePeriodo->get(request()->user());
        $configuraciones = $periodo === null
            ? collect()
            : EstablecimientoPeriodoConfiguracionGrado::query()
                ->where('establecimiento_periodo_id', $periodo->id)
                ->with('grados')
                ->orderBy('nombre')
                ->get();

        return view('admin.configuracion-grados.index', compact('periodo', 'configuraciones'));
    }

    public function create(): View
    {
        return view('admin.configuracion-grados.form', $this->formData(
            $this->activePeriodo->get(request()->user()),
        ) + ['configuracion' => null]);
    }

    public function store(StoreEstablecimientoPeriodoConfiguracionGradoRequest $request): RedirectResponse
    {
        $periodo = $this->periodoActivo();
        $datos = $request->validated();

        try {
            $configuracion = DB::transaction(function () use ($datos, $periodo): EstablecimientoPeriodoConfiguracionGrado {
                $configuracion = EstablecimientoPeriodoConfiguracionGrado::query()->create([
                    'establecimiento_periodo_id' => $periodo->id,
                    'nombre' => $datos['nombre'],
                    'porcentaje_examen_final' => $datos['porcentaje_examen_final'],
                    'porcentaje_proyecto_final' => $datos['porcentaje_proyecto_final'],
                ]);
                $configuracion->grados()->sync($this->datosPivoteGrados($datos['grados'], $periodo));
                $this->guardarPonderaciones($configuracion, $datos);

                return $configuracion;
            });
        } catch (UniqueConstraintViolationException) {
            return back()->withInput()->withErrors([
                'grados' => 'Un grado seleccionado ya pertenece a otra configuración.',
            ]);
        }

        return redirect()->route('Admin.configuracion-grados.edit', $configuracion)
            ->with('status', 'configuracion-grados-created');
    }

    public function edit(EstablecimientoPeriodoConfiguracionGrado $configuracionGrado): View
    {
        $periodo = $this->periodoActivo();
        abort_unless((int) $configuracionGrado->establecimiento_periodo_id === (int) $periodo->id, 404);
        $configuracionGrado->load(['grados', 'ciclos']);

        return view('admin.configuracion-grados.form', $this->formData($periodo, $configuracionGrado) + [
            'configuracion' => $configuracionGrado,
        ]);
    }

    public function update(
        StoreEstablecimientoPeriodoConfiguracionGradoRequest $request,
        EstablecimientoPeriodoConfiguracionGrado $configuracionGrado,
    ): RedirectResponse {
        $periodo = $this->periodoActivo();
        abort_unless((int) $configuracionGrado->establecimiento_periodo_id === (int) $periodo->id, 404);
        $datos = $request->validated();

        try {
            DB::transaction(function () use ($configuracionGrado, $datos): void {
                $configuracionGrado->update([
                    'nombre' => $datos['nombre'],
                    'porcentaje_examen_final' => $datos['porcentaje_examen_final'],
                    'porcentaje_proyecto_final' => $datos['porcentaje_proyecto_final'],
                ]);
                $configuracionGrado->grados()->sync($this->datosPivoteGrados($datos['grados'], $this->periodoActivo()));
                $configuracionGrado->ciclos()->delete();
                $this->guardarPonderaciones($configuracionGrado, $datos);
            });
        } catch (UniqueConstraintViolationException) {
            return back()->withInput()->withErrors([
                'grados' => 'Un grado seleccionado ya pertenece a otra configuración.',
            ]);
        }

        return redirect()->route('Admin.configuracion-grados.edit', $configuracionGrado)
            ->with('status', 'configuracion-grados-updated');
    }

    public function destroy(EstablecimientoPeriodoConfiguracionGrado $configuracionGrado): RedirectResponse
    {
        $periodo = $this->periodoActivo();
        abort_unless((int) $configuracionGrado->establecimiento_periodo_id === (int) $periodo->id, 404);
        abort_if($periodo->estaAbierto(), 403);
        $configuracionGrado->delete();

        return redirect()->route('Admin.configuracion-grados.index')
            ->with('status', 'configuracion-grados-deleted');
    }

    /** @return array<string, mixed> */
    private function formData(?EstablecimientoPeriodo $periodo, ?EstablecimientoPeriodoConfiguracionGrado $configuracion = null): array
    {
        $periodo?->loadMissing('ciclos');
        $gradosDisponibles = $this->gradosCuantitativos->paraOferta($periodo?->establecimiento_modalidad_jornada_id);

        if ($periodo instanceof EstablecimientoPeriodo) {
            $gradosDisponibles = $gradosDisponibles->except(
                EstablecimientoPeriodoConfiguracionGrado::gradoIdsOcupados($periodo->id, $configuracion?->id),
            );
        }
        $gradosConfigurados = $configuracion?->grados->modelKeys() ?? [];
        $seleccionados = old('grados', array_map('strval', $gradosConfigurados));
        $ciclosGuardados = $configuracion?->ciclos->keyBy('ciclo_orden') ?? collect();
        $ciclosAnteriores = old('ciclos', []);
        $ciclosFormulario = $periodo?->ciclos->map(function ($ciclo) use ($ciclosAnteriores, $ciclosGuardados): array {
            $guardado = $ciclosGuardados->get($ciclo->orden);
            $anterior = $ciclosAnteriores[$ciclo->orden] ?? [];

            return [
                'orden' => $ciclo->orden,
                'nombre' => $ciclo->nombre,
                'porcentaje_periodo' => $ciclosAnteriores[$ciclo->orden]['porcentaje_periodo']
                    ?? $guardado?->porcentaje_periodo
                    ?? $ciclo->porcentaje,
                'porcentaje_insumos' => $anterior['porcentaje_insumos'] ?? $guardado?->porcentaje_insumos ?? $ciclo->porcentaje_insumos,
                'porcentaje_examen' => $anterior['porcentaje_examen'] ?? $guardado?->porcentaje_examen ?? $ciclo->porcentaje_examen,
                'porcentaje_proyecto' => $anterior['porcentaje_proyecto'] ?? $guardado?->porcentaje_proyecto ?? $ciclo->porcentaje_proyecto,
            ];
        })->all() ?? [];

        return [
            'periodo' => $periodo,
            'gradosFormulario' => $gradosDisponibles->map(fn (array $grado, int|string $gradoId): array => [
                'id' => (string) $gradoId,
                'nombre' => $grado['nombre'],
            ])->values()->all(),
            'ciclosFormulario' => $ciclosFormulario,
            'gradosSeleccionados' => array_map('strval', is_array($seleccionados) ? $seleccionados : []),
            'porcentajeExamenFinal' => old('porcentaje_examen_final', $configuracion?->porcentaje_examen_final ?? $periodo?->porcentaje_examen_final ?? '0.00'),
            'porcentajeProyectoFinal' => old('porcentaje_proyecto_final', $configuracion?->porcentaje_proyecto_final ?? $periodo?->porcentaje_proyecto_final ?? '0.00'),
        ];
    }

    /** @param array<string, mixed> $datos */
    private function guardarPonderaciones(EstablecimientoPeriodoConfiguracionGrado $configuracion, array $datos): void
    {
        foreach ($datos['ciclos'] as $orden => $componentes) {
            $configuracion->ciclos()->create([
                'ciclo_orden' => $orden,
                'porcentaje_periodo' => number_format((float) $datos['ponderaciones_periodo'][$orden], 2, '.', ''),
                'porcentaje_insumos' => number_format((float) $componentes['porcentaje_insumos'], 2, '.', ''),
                'porcentaje_examen' => number_format((float) $componentes['porcentaje_examen'], 2, '.', ''),
                'porcentaje_proyecto' => number_format((float) $componentes['porcentaje_proyecto'], 2, '.', ''),
            ]);
        }
    }

    /** @param list<int|string> $grados */
    private function datosPivoteGrados(array $grados, EstablecimientoPeriodo $periodo): array
    {
        return collect($grados)
            ->mapWithKeys(fn (int|string $gradoId): array => [(int) $gradoId => ['establecimiento_periodo_id' => $periodo->id]])
            ->all();
    }

    private function periodoActivo(): EstablecimientoPeriodo
    {
        $periodo = $this->activePeriodo->get(request()->user());
        abort_if($periodo === null, 404);

        return $periodo;
    }
}
