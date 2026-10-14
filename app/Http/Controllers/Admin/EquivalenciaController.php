<?php

namespace App\Http\Controllers\Admin;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Http\Controllers\Controller;
use App\Models\Equivalencia;
use App\Models\EquivalenciaDetalle;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EquivalenciaController extends Controller
{
    public function __construct(private ActiveOferta $activeOferta, private ActivePeriodo $activePeriodo) {}

    public function index(): View
    {
        return view('admin.tablas-equivalencias.index', ['equivalencias' => Equivalencia::query()->orderBy('nombre')->get()]);
    }

    public function create(): View
    {
        return view('admin.tablas-equivalencias.form', ['equivalencia' => new Equivalencia(['activo' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Equivalencia::query()->create($this->validatedAttributes($request));

        return redirect()->route('Admin.tablas-equivalencias.index')->with('status', 'equivalencia-created');
    }

    public function edit(Equivalencia $equivalencia): View
    {
        return view('admin.tablas-equivalencias.form', compact('equivalencia'));
    }

    public function update(Request $request, Equivalencia $equivalencia): RedirectResponse
    {
        $equivalencia->update($this->validatedAttributes($request));

        return redirect()->route('Admin.tablas-equivalencias.index')->with('status', 'equivalencia-updated');
    }

    public function destroy(Equivalencia $equivalencia): RedirectResponse
    {
        $equivalencia->delete();

        return redirect()->route('Admin.tablas-equivalencias.index')->with('status', 'equivalencia-deleted');
    }

    public function detalles(Equivalencia $equivalencia): View
    {
        $equivalencia->load(['detalles' => fn ($query) => $query->orderBy('id')]);

        return view('admin.tablas-equivalencias.detalles', compact('equivalencia'));
    }

    public function storeDetalle(Request $request, Equivalencia $equivalencia): RedirectResponse
    {
        $equivalencia->detalles()->create($this->validatedDetalleAttributes($request));

        return redirect()->route('Admin.tablas-equivalencias.detalles.index', $equivalencia)->with('status', 'equivalencia-detalle-created');
    }

    public function updateDetalle(Request $request, Equivalencia $equivalencia, EquivalenciaDetalle $detalle): RedirectResponse
    {
        $detalle->update($this->validatedDetalleAttributes($request));

        return redirect()->route('Admin.tablas-equivalencias.detalles.index', $equivalencia)->with('status', 'equivalencia-detalle-updated');
    }

    public function destroyDetalle(Equivalencia $equivalencia, EquivalenciaDetalle $detalle): RedirectResponse
    {
        $detalle->delete();

        return redirect()->route('Admin.tablas-equivalencias.detalles.index', $equivalencia)->with('status', 'equivalencia-detalle-deleted');
    }

    public function asignaturas(Request $request, Equivalencia $equivalencia): View|RedirectResponse
    {
        $user = $request->user();
        abort_if($user?->establecimiento_id === null, 403);
        if ($this->activeOferta->needsSelection($user)) {
            return redirect()->guest(route('oferta.select'));
        }
        $oferta = $this->activeOferta->get($user);
        $periodo = $this->activePeriodo->get($user);
        $filas = collect();
        if ($oferta !== null && $periodo !== null) {
            $aulas = $this->aulas($user->establecimiento_id, $oferta->id, $periodo->id);
            $asignaturas = EstablecimientoAsignatura::query()->with(['asignatura', 'asignaturaPropia'])
                ->where('establecimiento_id', $user->establecimiento_id)->where('establecimiento_modalidad_jornada_id', $oferta->id)
                ->where('tipo_calificacion', 'cuantitativa')->get()->groupBy('grado_id');
            $actuales = DB::table('establecimiento_aula_asignatura_equivalencias')->whereIn('establecimiento_aula_id', $aulas->pluck('id'))
                ->get()->keyBy(fn (object $row): string => $row->establecimiento_aula_id.':'.$row->establecimiento_asignatura_id);
            $filas = $aulas->flatMap(fn (EstablecimientoAula $aula) => $asignaturas->get($aula->establecimientoGrado?->grado_id, collect())->map(function (EstablecimientoAsignatura $asignatura) use ($aula, $actuales, $equivalencia): array {
                $key = $aula->id.':'.$asignatura->id;
                $actual = $actuales->get($key);

                return ['key' => $key, 'asignatura' => $asignatura->asignaturaPropia?->nombre ?? $asignatura->asignatura?->nombre ?? 'Asignatura', 'grado' => $aula->etiqueta(), 'actual' => $actual, 'pertenece' => (int) ($actual?->equivalencia_id ?? 0) === (int) $equivalencia->id];
            }))->values();
        }

        return view('admin.tablas-equivalencias.asignaturas', ['equivalencia' => $equivalencia, 'filas' => $filas, 'periodoAbierto' => $periodo?->estaAbierto() ?? false]);
    }

    public function actualizarAsignaturas(Request $request, Equivalencia $equivalencia): RedirectResponse
    {
        $user = $request->user();
        abort_if($user?->establecimiento_id === null, 403);
        if ($this->activeOferta->needsSelection($user)) {
            return redirect()->guest(route('oferta.select'));
        }
        $oferta = $this->activeOferta->get($user);
        $periodo = $this->activePeriodo->get($user);
        abort_if($oferta === null || $periodo === null, 403);
        abort_if($periodo->estaAbierto(), 403, 'No se puede cambiar la asignación de un periodo aperturado.');
        $aulas = $this->aulas($user->establecimiento_id, $oferta->id, $periodo->id);
        $asignaturas = EstablecimientoAsignatura::query()->where('establecimiento_id', $user->establecimiento_id)
            ->where('establecimiento_modalidad_jornada_id', $oferta->id)->where('tipo_calificacion', 'cuantitativa')->get()->keyBy('id');
        $permitidas = $aulas->flatMap(fn (EstablecimientoAula $aula) => $asignaturas
            ->filter(fn (EstablecimientoAsignatura $asignatura): bool => (int) $asignatura->grado_id === (int) $aula->establecimientoGrado?->grado_id)
            ->keys()->map(fn ($id): string => $aula->id.':'.$id));
        $seleccionadas = collect($request->validate(['asignaciones' => ['sometimes', 'array'], 'asignaciones.*' => ['string', 'regex:/^\d+:\d+$/', 'distinct']])['asignaciones'] ?? []);
        if ($seleccionadas->diff($permitidas)->isNotEmpty()) {
            abort(422, 'Selecciona únicamente asignaturas cuantitativas del periodo activo.');
        }
        $clavesOcupadasPorOtraEquivalencia = DB::table('establecimiento_aula_asignatura_equivalencias')
            ->whereIn('establecimiento_aula_id', $aulas->pluck('id'))
            ->where('equivalencia_id', '!=', $equivalencia->id)
            ->get()
            ->map(fn (object $row): string => $row->establecimiento_aula_id.':'.$row->establecimiento_asignatura_id);
        if ($seleccionadas->intersect($clavesOcupadasPorOtraEquivalencia)->isNotEmpty()) {
            abort(422, 'Una asignatura seleccionada ya pertenece a otra tabla de equivalencias.');
        }
        DB::transaction(function () use ($aulas, $equivalencia, $seleccionadas): void {
            $claves = $seleccionadas->flip();
            $actuales = DB::table('establecimiento_aula_asignatura_equivalencias')->whereIn('establecimiento_aula_id', $aulas->pluck('id'))->get();
            $quitar = $actuales->filter(fn (object $row): bool => (int) $row->equivalencia_id === (int) $equivalencia->id && ! $claves->has($row->establecimiento_aula_id.':'.$row->establecimiento_asignatura_id))->pluck('id');
            if ($quitar->isNotEmpty()) {
                DB::table('establecimiento_aula_asignatura_equivalencias')->whereIn('id', $quitar)->delete();
            }
            $rows = $seleccionadas->map(function (string $key) use ($equivalencia): array {
                [$aulaId, $asignaturaId] = array_map('intval', explode(':', $key));

                return ['establecimiento_aula_id' => $aulaId, 'establecimiento_asignatura_id' => $asignaturaId, 'equivalencia_id' => $equivalencia->id, 'created_at' => now(), 'updated_at' => now()];
            })->all();
            if ($rows !== []) {
                DB::table('establecimiento_aula_asignatura_equivalencias')->upsert($rows, ['establecimiento_aula_id', 'establecimiento_asignatura_id'], ['equivalencia_id', 'updated_at']);
            }
        });

        return redirect()->route('Admin.tablas-equivalencias.asignaturas.index', $equivalencia)->with('status', 'equivalencia-asignaturas-updated');
    }

    /** @return Collection<int, EstablecimientoAula> */
    private function aulas(int $establecimientoId, int $ofertaId, int $periodoId): Collection
    {
        return EstablecimientoAula::query()->with('establecimientoGrado.grado')->where('establecimiento_id', $establecimientoId)
            ->where('establecimiento_modalidad_jornada_id', $ofertaId)->where('establecimiento_periodo_id', $periodoId)
            ->orderBy('establecimiento_grado_id')->orderBy('paralelo')->get();
    }

    /** @return array{nombre: string, descripcion: ?string, activo: bool} */
    private function validatedAttributes(Request $request): array
    {
        $data = $request->validate(['nombre' => ['required', 'string', 'max:255'], 'descripcion' => ['nullable', 'string'], 'activo' => ['required', 'boolean']]);

        return ['nombre' => $data['nombre'], 'descripcion' => $data['descripcion'] ?? null, 'activo' => (bool) $data['activo']];
    }

    /** @return array{nombre: string, descripcion: ?string, valor: ?string, minimo: ?string, maximo: ?string, activo: bool} */
    private function validatedDetalleAttributes(Request $request): array
    {
        $data = $request->validate(['nombre' => ['required', 'string', 'max:255'], 'descripcion' => ['nullable', 'string'], 'valor' => ['nullable', 'string', 'max:100'], 'minimo' => ['nullable', 'numeric', 'decimal:0,2'], 'maximo' => ['nullable', 'numeric', 'decimal:0,2'], 'activo' => ['required', 'boolean']]);
        if (isset($data['minimo'], $data['maximo']) && (float) $data['minimo'] > (float) $data['maximo']) {
            abort(422, 'El mínimo no puede ser mayor que el máximo.');
        }

        return ['nombre' => $data['nombre'], 'descripcion' => $data['descripcion'] ?? null, 'valor' => $data['valor'] ?? null, 'minimo' => $data['minimo'] ?? null, 'maximo' => $data['maximo'] ?? null, 'activo' => (bool) $data['activo']];
    }
}
