<?php

namespace App\Http\Controllers\Admin;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\TipoCalificacion;
use App\Http\Controllers\Controller;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoPeriodo;
use App\Models\TipoInsumo;
use App\Models\TipoInsumoDetalle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TipoInsumoController extends Controller
{
    public function __construct(
        private ActiveOferta $activeOferta,
        private ActivePeriodo $activePeriodo,
    ) {}

    public function index(Request $request): View
    {
        $periodo = $this->activePeriodo->get($request->user());

        return view('admin.tipos-insumos.index', [
            'periodo' => $periodo,
            'tiposInsumos' => $periodo === null
                ? collect()
                : TipoInsumo::query()
                    ->where('establecimiento_periodo_id', $periodo->id)
                    ->orderBy('nombre')
                    ->get(),
        ]);
    }

    public function create(): View
    {
        $this->periodoActivo();

        return view('admin.tipos-insumos.form', [
            'tipoInsumo' => new TipoInsumo(['activo' => true, 'tipo_calificacion' => TipoCalificacion::Cuantitativa->value]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $periodo = $this->periodoActivo();

        TipoInsumo::query()->create([
            ...$this->validatedAttributes($request),
            'establecimiento_periodo_id' => $periodo->id,
        ]);

        return redirect()->route('Admin.tipos-insumos.index')->with('status', 'tipos-insumos-created');
    }

    public function edit(TipoInsumo $tipoInsumo): View
    {
        $this->asegurarDelPeriodo($tipoInsumo);

        return view('admin.tipos-insumos.form', compact('tipoInsumo'));
    }

    public function update(Request $request, TipoInsumo $tipoInsumo): RedirectResponse
    {
        $this->asegurarDelPeriodo($tipoInsumo);
        $tipoInsumo->update($this->validatedAttributes($request));

        return redirect()->route('Admin.tipos-insumos.index')->with('status', 'tipos-insumos-updated');
    }

    public function destroy(TipoInsumo $tipoInsumo): RedirectResponse
    {
        $this->asegurarDelPeriodo($tipoInsumo);
        $tipoInsumo->delete();

        return redirect()->route('Admin.tipos-insumos.index')->with('status', 'tipos-insumos-deleted');
    }

    public function asignaturas(Request $request, TipoInsumo $tipoInsumo): View|RedirectResponse
    {
        $this->asegurarDelPeriodo($tipoInsumo);
        $user = $request->user();
        $establecimientoId = $user?->establecimiento_id;

        abort_if($establecimientoId === null, 403);

        if ($this->activeOferta->needsSelection($user)) {
            return redirect()->guest(route('oferta.select'));
        }

        $oferta = $this->activeOferta->get($user);
        $periodo = $this->activePeriodo->get($user);
        $aulas = $oferta === null || $periodo === null
            ? collect()
            : $this->aulasPorContexto($establecimientoId, $oferta->id, $periodo->id);
        $asignaturasPorGrado = $oferta === null
            ? collect()
            : $this->asignaturasPorGrado($establecimientoId, $oferta->id);

        $columnasOrdenables = [
            'id' => 'id',
            'asignatura' => 'asignatura',
            'grado' => 'grado',
            'tipo' => 'tipoAsignado',
        ];
        $orden = $request->query('orden', 'id');
        $direccion = $request->query('direccion', 'asc');
        $busqueda = trim((string) $request->query('buscar', ''));
        $tipoCalificacionFiltro = $request->query('tipo_calificacion', '');
        $tipoCalificacionFiltro = is_string($tipoCalificacionFiltro) && $tipoCalificacionFiltro === $tipoInsumo->tipo_calificacion
            ? $tipoCalificacionFiltro
            : '';
        $orden = is_string($orden) && array_key_exists($orden, $columnasOrdenables) ? $orden : 'id';
        $direccion = in_array($direccion, ['asc', 'desc'], true) ? $direccion : 'asc';
        $filas = $this->filasAsignaturas($aulas, $asignaturasPorGrado, $tipoInsumo);

        if ($busqueda !== '') {
            $termino = Str::lower($busqueda);
            $filas = $filas->filter(fn (array $fila): bool => Str::contains(
                Str::lower($fila['id'].' '.$fila['asignatura'].' '.$fila['grado'].' '.$fila['tipoAsignado']),
                $termino,
            ));
        }

        if ($tipoCalificacionFiltro !== '') {
            $filas = $filas->filter(fn (array $fila): bool => $fila['tipoCalificacion'] === $tipoCalificacionFiltro);
        }

        $filas = $filas
            ->sortBy($columnasOrdenables[$orden], SORT_NATURAL | SORT_FLAG_CASE, $direccion === 'desc')
            ->values();
        $porPagina = 15;
        $pagina = LengthAwarePaginator::resolveCurrentPage();
        $asignaciones = new LengthAwarePaginator(
            $filas->forPage($pagina, $porPagina)->values(),
            $filas->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('admin.tipos-insumos.asignaturas', [
            'tipoInsumo' => $tipoInsumo,
            'asignaciones' => $asignaciones,
            'orden' => $orden,
            'direccion' => $direccion,
            'busqueda' => $busqueda,
            'tipoCalificacionFiltro' => $tipoCalificacionFiltro,
            'periodoAbierto' => $periodo?->estaAbierto() ?? false,
        ]);
    }

    public function actualizarAsignaturas(Request $request, TipoInsumo $tipoInsumo): RedirectResponse
    {
        $this->asegurarDelPeriodo($tipoInsumo);
        $user = $request->user();
        $establecimientoId = $user?->establecimiento_id;

        abort_if($establecimientoId === null, 403);

        if ($this->activeOferta->needsSelection($user)) {
            return redirect()->guest(route('oferta.select'));
        }

        $oferta = $this->activeOferta->get($user);
        $periodo = $this->activePeriodo->get($user);

        abort_if($oferta === null || $periodo === null, 403);
        abort_if($periodo->estaAbierto(), 403, 'No se puede cambiar la asignación de un periodo aperturado.');

        $aulas = $this->aulasPorContexto($establecimientoId, $oferta->id, $periodo->id);
        $asignaturasPorGrado = $this->asignaturasPorGrado($establecimientoId, $oferta->id);
        $filas = $this->filasAsignaturas($aulas, $asignaturasPorGrado, $tipoInsumo)->keyBy('key');

        $validated = $request->validate([
            'buscar' => ['nullable', 'string', 'max:255'],
            'tipo_calificacion' => ['nullable', Rule::in([$tipoInsumo->tipo_calificacion])],
            'visibles' => ['sometimes', 'array'],
            'visibles.*' => ['string', 'regex:/^\\d+:\\d+$/', 'distinct'],
            'asignaciones' => ['sometimes', 'array'],
            'asignaciones.*' => ['string', 'regex:/^\\d+:\\d+$/', 'distinct'],
        ]);
        $clavesSeleccionadas = collect($validated['asignaciones'] ?? [])->values();
        $clavesVisibles = collect($validated['visibles'] ?? $filas->keys())->values();
        $clavesAsignadasAOtroTipo = $filas
            ->filter(fn (array $fila): bool => $fila['tipoAsignadoId'] !== null && $fila['tipoAsignadoId'] !== (int) $tipoInsumo->id)
            ->keys();
        $clavesConTipoIncompatible = $filas
            ->filter(fn (array $fila): bool => ! $fila['compatible'])
            ->keys();

        if ($clavesVisibles->diff($filas->keys())->isNotEmpty()
            || $clavesSeleccionadas->diff($clavesVisibles)->isNotEmpty()
            || $clavesSeleccionadas->intersect($clavesAsignadasAOtroTipo)->isNotEmpty()
            || $clavesSeleccionadas->intersect($clavesConTipoIncompatible)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'asignaciones' => 'Selecciona únicamente asignaturas de las aulas del periodo activo.',
            ]);
        }

        DB::transaction(function () use ($aulas, $filas, $clavesVisibles, $clavesSeleccionadas, $tipoInsumo): void {
            $actuales = DB::table('establecimiento_aula_asignatura_tipo_insumos')
                ->whereIn('establecimiento_aula_id', $aulas->pluck('id'))
                ->where('tipo_insumo_id', $tipoInsumo->id)
                ->get();
            $idsParaQuitar = $actuales
                ->filter(function (object $asignacion) use ($clavesVisibles, $clavesSeleccionadas, $filas): bool {
                    $clave = $asignacion->establecimiento_aula_id.':'.$asignacion->establecimiento_asignatura_id;

                    return $clavesVisibles->contains($clave)
                        && ($filas->get($clave)['compatible'] ?? false)
                        && ! $clavesSeleccionadas->contains($clave);
                })
                ->pluck('id');

            if ($idsParaQuitar->isNotEmpty()) {
                DB::table('establecimiento_aula_asignatura_tipo_insumos')->whereIn('id', $idsParaQuitar)->delete();
            }

            $filasParaGuardar = $clavesSeleccionadas
                ->map(fn (string $clave): array => $filas->get($clave))
                ->map(fn (array $fila): array => [
                    'establecimiento_aula_id' => $fila['aulaId'],
                    'establecimiento_asignatura_id' => $fila['asignaturaId'],
                    'tipo_insumo_id' => $tipoInsumo->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->all();

            if ($filasParaGuardar !== []) {
                DB::table('establecimiento_aula_asignatura_tipo_insumos')->upsert(
                    $filasParaGuardar,
                    ['establecimiento_aula_id', 'establecimiento_asignatura_id'],
                    ['tipo_insumo_id', 'updated_at'],
                );
            }
        });

        $orden = $request->input('orden', 'id');
        $direccion = $request->input('direccion', 'asc');
        $pagina = max(1, (int) $request->input('pagina', 1));
        $busqueda = trim((string) ($validated['buscar'] ?? ''));
        $tipoCalificacionFiltro = $validated['tipo_calificacion'] ?? '';
        $parametrosRuta = [
            'tipoInsumo' => $tipoInsumo,
            'orden' => in_array($orden, ['id', 'asignatura', 'grado', 'tipo'], true) ? $orden : 'id',
            'direccion' => in_array($direccion, ['asc', 'desc'], true) ? $direccion : 'asc',
            'page' => $pagina,
        ];

        if ($busqueda !== '') {
            $parametrosRuta['buscar'] = $busqueda;
        }

        if ($tipoCalificacionFiltro !== '') {
            $parametrosRuta['tipo_calificacion'] = $tipoCalificacionFiltro;
        }

        return redirect()
            ->route('Admin.tipos-insumos.asignaturas.index', $parametrosRuta)
            ->with('status', 'tipo-insumo-asignaturas-updated');
    }

    public function detalles(TipoInsumo $tipoInsumo): View
    {
        $this->asegurarDelPeriodo($tipoInsumo);
        $tipoInsumo->load(['detalles' => fn ($query) => $query->orderBy('id')]);

        return view('admin.tipos-insumos.detalles', [
            'tipoInsumo' => $tipoInsumo,
            'totalPorcentajeActivo' => (float) $tipoInsumo->detalles
                ->where('activo', true)
                ->sum(fn (TipoInsumoDetalle $detalle): float => (float) $detalle->porcentaje),
        ]);
    }

    public function storeDetalle(Request $request, TipoInsumo $tipoInsumo): RedirectResponse
    {
        $this->asegurarDelPeriodo($tipoInsumo);
        $tipoInsumo->detalles()->create($this->validatedDetalleAttributes($request, $tipoInsumo));

        return redirect()->route('Admin.tipos-insumos.detalles.index', $tipoInsumo)->with('status', 'tipo-insumo-detalle-created');
    }

    public function updateDetalle(Request $request, TipoInsumo $tipoInsumo, TipoInsumoDetalle $detalle): RedirectResponse
    {
        $this->asegurarDelPeriodo($tipoInsumo);
        $detalle->update($this->validatedDetalleAttributes($request, $tipoInsumo));

        return redirect()->route('Admin.tipos-insumos.detalles.index', $tipoInsumo)->with('status', 'tipo-insumo-detalle-updated');
    }

    public function destroyDetalle(TipoInsumo $tipoInsumo, TipoInsumoDetalle $detalle): RedirectResponse
    {
        $this->asegurarDelPeriodo($tipoInsumo);
        $detalle->delete();

        return redirect()->route('Admin.tipos-insumos.detalles.index', $tipoInsumo)->with('status', 'tipo-insumo-detalle-deleted');
    }

    private function periodoActivo(): EstablecimientoPeriodo
    {
        $periodo = $this->activePeriodo->get(request()->user());
        abort_if($periodo === null, 404);

        return $periodo;
    }

    private function asegurarDelPeriodo(TipoInsumo $tipoInsumo): void
    {
        abort_unless((int) $tipoInsumo->establecimiento_periodo_id === (int) $this->periodoActivo()->id, 404);
    }

    /**
     * @return array{nombre: string, descripcion: ?string, tipo_calificacion: string, activo: bool}
     */
    private function validatedAttributes(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'tipo_calificacion' => ['required', Rule::enum(TipoCalificacion::class)],
            'activo' => ['required', 'boolean'],
        ]);
    }

    /**
     * @return Collection<int, EstablecimientoAula>
     */
    private function aulasPorContexto(int $establecimientoId, int $ofertaId, int $periodoId): Collection
    {
        return EstablecimientoAula::query()
            ->with('establecimientoGrado.grado')
            ->where('establecimiento_id', $establecimientoId)
            ->where('establecimiento_modalidad_jornada_id', $ofertaId)
            ->where('establecimiento_periodo_id', $periodoId)
            ->orderBy('establecimiento_grado_id')
            ->orderBy('paralelo')
            ->get();
    }

    /**
     * @return Collection<int, Collection<int, EstablecimientoAsignatura>>
     */
    private function asignaturasPorGrado(int $establecimientoId, int $ofertaId): Collection
    {
        return EstablecimientoAsignatura::query()
            ->with(['asignatura', 'asignaturaPropia'])
            ->where('establecimiento_id', $establecimientoId)
            ->where('establecimiento_modalidad_jornada_id', $ofertaId)
            ->orderBy('id')
            ->get()
            ->groupBy('grado_id');
    }

    /**
     * @param  Collection<int, EstablecimientoAula>  $aulas
     * @param  Collection<int, Collection<int, EstablecimientoAsignatura>>  $asignaturasPorGrado
     * @return Collection<int, array{key: string, id: int, aulaId: int, asignaturaId: int, asignatura: string, grado: string, tipoAsignado: string, tipoAsignadoId: ?int, estaAsignada: bool, pertenece: bool, compatible: bool, tipoCalificacion: string}>
     */
    private function filasAsignaturas(Collection $aulas, Collection $asignaturasPorGrado, TipoInsumo $tipoInsumo): Collection
    {
        if ($aulas->isEmpty()) {
            return collect();
        }

        $tiposAsignados = DB::table('establecimiento_aula_asignatura_tipo_insumos as asignacion')
            ->join('tipos_insumos as tipo', 'tipo.id', '=', 'asignacion.tipo_insumo_id')
            ->whereIn('asignacion.establecimiento_aula_id', $aulas->pluck('id'))
            ->get([
                'asignacion.establecimiento_aula_id',
                'asignacion.establecimiento_asignatura_id',
                'asignacion.tipo_insumo_id',
                'tipo.nombre as tipo_insumo_nombre',
            ])
            ->keyBy(fn (object $asignacion): string => $asignacion->establecimiento_aula_id.':'.$asignacion->establecimiento_asignatura_id);

        return $aulas->flatMap(function (EstablecimientoAula $aula) use ($asignaturasPorGrado, $tiposAsignados, $tipoInsumo): Collection {
            $gradoId = $aula->establecimientoGrado?->grado_id;

            return $asignaturasPorGrado->get($gradoId, collect())
                ->map(function (EstablecimientoAsignatura $asignatura) use ($aula, $tiposAsignados, $tipoInsumo): array {
                    $key = $aula->id.':'.$asignatura->id;
                    $tipoAsignado = $tiposAsignados->get($key);

                    return [
                        'key' => $key,
                        'id' => (int) $asignatura->id,
                        'aulaId' => (int) $aula->id,
                        'asignaturaId' => (int) $asignatura->id,
                        'asignatura' => $asignatura->asignaturaPropia?->nombre ?? $asignatura->asignatura?->nombre ?? 'Asignatura',
                        'grado' => $aula->etiqueta(),
                        'tipoAsignado' => $tipoAsignado?->tipo_insumo_nombre ?? '—',
                        'tipoAsignadoId' => $tipoAsignado === null ? null : (int) $tipoAsignado->tipo_insumo_id,
                        'estaAsignada' => $tipoAsignado !== null,
                        'pertenece' => (int) ($tipoAsignado?->tipo_insumo_id ?? 0) === (int) $tipoInsumo->id,
                        'compatible' => $asignatura->tipo_calificacion?->value === $tipoInsumo->tipo_calificacion,
                        'tipoCalificacion' => $asignatura->tipo_calificacion?->value ?? '',
                    ];
                });
        })->values();
    }

    /**
     * @return array{nombre: string, descripcion: ?string, valor: ?string, porcentaje: ?string, activo: bool}
     */
    private function validatedDetalleAttributes(Request $request, TipoInsumo $tipoInsumo): array
    {
        $rules = [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['required', 'boolean'],
        ];

        if ($tipoInsumo->tipo_calificacion === TipoCalificacion::Cuantitativa->value) {
            $rules['porcentaje'] = ['required', 'numeric', 'min:0', 'max:100'];
        } else {
            $rules['valor'] = ['required', 'string', 'max:100'];
        }

        $attributes = $request->validate($rules);

        return [
            'nombre' => $attributes['nombre'],
            'descripcion' => $attributes['descripcion'] ?? null,
            'valor' => $attributes['valor'] ?? null,
            'porcentaje' => $attributes['porcentaje'] ?? null,
            'activo' => (bool) $attributes['activo'],
        ];
    }
}
