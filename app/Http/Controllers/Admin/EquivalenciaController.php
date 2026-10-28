<?php

namespace App\Http\Controllers\Admin;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\UsoEquivalencia;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertEquivalenciaDetalleRequest;
use App\Models\Equivalencia;
use App\Models\EquivalenciaDetalle;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoComportamiento;
use App\Models\EstablecimientoPeriodo;
use App\Services\EscalaComportamiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EquivalenciaController extends Controller
{
    public function __construct(private ActiveOferta $activeOferta, private ActivePeriodo $activePeriodo) {}

    public function index(Request $request): View
    {
        $periodo = $this->activePeriodo->get($request->user());

        $equivalencias = $periodo === null
            ? collect()
            : Equivalencia::query()
                ->where('establecimiento_periodo_id', $periodo->id)
                ->orderBy('nombre')
                ->get();

        return view('admin.tablas-equivalencias.index', [
            'periodo' => $periodo,
            'equivalencias' => $equivalencias,
            'tieneComportamiento' => $equivalencias->contains(
                fn (Equivalencia $equivalencia): bool => $equivalencia->esComportamiento(),
            ),
        ]);
    }

    public function create(): View
    {
        $this->periodoActivo();

        return view('admin.tablas-equivalencias.form', ['equivalencia' => new Equivalencia(['activo' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $periodo = $this->periodoActivo();

        Equivalencia::query()->create([
            ...$this->validatedAttributes($request, $periodo),
            'establecimiento_periodo_id' => $periodo->id,
        ]);

        return redirect()->route('Admin.tablas-equivalencias.index')->with('status', 'equivalencia-created');
    }

    public function storeComportamiento(EscalaComportamiento $escala): RedirectResponse
    {
        $periodo = $this->periodoActivo();
        $existente = Equivalencia::query()
            ->where('establecimiento_periodo_id', $periodo->id)
            ->where('uso', UsoEquivalencia::Comportamiento)
            ->first();

        if ($existente instanceof Equivalencia) {
            return redirect()->route('Admin.tablas-equivalencias.detalles.index', $existente);
        }

        $equivalencia = $escala->crearReglamento($periodo);

        return redirect()
            ->route('Admin.tablas-equivalencias.detalles.index', $equivalencia)
            ->with('status', 'equivalencia-created');
    }

    public function edit(Equivalencia $equivalencia): View
    {
        $this->asegurarDelPeriodo($equivalencia);

        return view('admin.tablas-equivalencias.form', compact('equivalencia'));
    }

    public function update(Request $request, Equivalencia $equivalencia): RedirectResponse
    {
        $this->asegurarDelPeriodo($equivalencia);
        $equivalencia->update($this->validatedAttributes($request, $this->periodoActivo(), $equivalencia));

        return redirect()->route('Admin.tablas-equivalencias.index')->with('status', 'equivalencia-updated');
    }

    public function destroy(Equivalencia $equivalencia): RedirectResponse
    {
        $this->asegurarDelPeriodo($equivalencia);

        if ($this->tieneCalificaciones($equivalencia)) {
            return back()->withErrors([
                'equivalencia' => 'Esta tabla ya calificó estudiantes. Desactívala en lugar de eliminarla.',
            ]);
        }

        $equivalencia->delete();

        return redirect()->route('Admin.tablas-equivalencias.index')->with('status', 'equivalencia-deleted');
    }

    public function detalles(Equivalencia $equivalencia): View
    {
        $this->asegurarDelPeriodo($equivalencia);
        $equivalencia->load(['detalles' => fn ($query) => $equivalencia->esComportamiento()
            ? $query->orderByRaw('jerarquia is null')->orderByDesc('jerarquia')->orderBy('id')
            : $query->orderBy('id')]);

        return view('admin.tablas-equivalencias.detalles', compact('equivalencia'));
    }

    public function storeDetalle(UpsertEquivalenciaDetalleRequest $request, Equivalencia $equivalencia): RedirectResponse
    {
        $this->asegurarDelPeriodo($equivalencia);
        $equivalencia->detalles()->create($request->detalle());

        return redirect()->route('Admin.tablas-equivalencias.detalles.index', $equivalencia)->with('status', 'equivalencia-detalle-created');
    }

    public function updateDetalle(UpsertEquivalenciaDetalleRequest $request, Equivalencia $equivalencia, EquivalenciaDetalle $detalle): RedirectResponse
    {
        $this->asegurarDelPeriodo($equivalencia);
        $detalle->update($request->detalle());

        return redirect()->route('Admin.tablas-equivalencias.detalles.index', $equivalencia)->with('status', 'equivalencia-detalle-updated');
    }

    public function destroyDetalle(Equivalencia $equivalencia, EquivalenciaDetalle $detalle): RedirectResponse
    {
        $this->asegurarDelPeriodo($equivalencia);

        if (EstablecimientoComportamiento::query()->where('equivalencia_detalle_id', $detalle->id)->exists()) {
            return back()->withErrors([
                'detalle' => 'Esta calificación ya fue usada en un aula. Desactívala en lugar de eliminarla.',
            ]);
        }

        $detalle->delete();

        return redirect()->route('Admin.tablas-equivalencias.detalles.index', $equivalencia)->with('status', 'equivalencia-detalle-deleted');
    }

    public function asignaturas(Request $request, Equivalencia $equivalencia): View|RedirectResponse
    {
        $this->asegurarDelPeriodo($equivalencia);
        abort_if($equivalencia->esComportamiento(), 404);
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
        $this->asegurarDelPeriodo($equivalencia);
        abort_if($equivalencia->esComportamiento(), 404);
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

    private function periodoActivo(): EstablecimientoPeriodo
    {
        $periodo = $this->activePeriodo->get(request()->user());
        abort_if($periodo === null, 404);

        return $periodo;
    }

    private function asegurarDelPeriodo(Equivalencia $equivalencia): void
    {
        abort_unless((int) $equivalencia->establecimiento_periodo_id === (int) $this->periodoActivo()->id, 404);
    }

    /** @return Collection<int, EstablecimientoAula> */
    private function aulas(int $establecimientoId, int $ofertaId, int $periodoId): Collection
    {
        return EstablecimientoAula::query()->with('establecimientoGrado.grado')->where('establecimiento_id', $establecimientoId)
            ->where('establecimiento_modalidad_jornada_id', $ofertaId)->where('establecimiento_periodo_id', $periodoId)
            ->orderBy('establecimiento_grado_id')->orderBy('paralelo')->get();
    }

    /**
     * @return array{nombre: string, descripcion: ?string, uso: UsoEquivalencia, activo: bool}
     */
    private function validatedAttributes(Request $request, EstablecimientoPeriodo $periodo, ?Equivalencia $actual = null): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'uso' => ['required', Rule::enum(UsoEquivalencia::class)],
            'activo' => ['required', 'boolean'],
        ]);
        $uso = UsoEquivalencia::from($data['uso']);

        if ($uso === UsoEquivalencia::Comportamiento) {
            $existe = Equivalencia::query()
                ->where('establecimiento_periodo_id', $periodo->id)
                ->where('uso', UsoEquivalencia::Comportamiento)
                ->when($actual !== null, fn ($query) => $query->whereKeyNot($actual->id))
                ->exists();

            if ($existe) {
                throw ValidationException::withMessages([
                    'uso' => 'Este periodo ya tiene una tabla de comportamiento.',
                ]);
            }
        }

        if ($actual?->esComportamiento() && $uso !== UsoEquivalencia::Comportamiento && $this->tieneCalificaciones($actual)) {
            throw ValidationException::withMessages([
                'uso' => 'Esta tabla ya calificó estudiantes. Conserva el uso de comportamiento.',
            ]);
        }

        return [
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'uso' => $uso,
            'activo' => (bool) $data['activo'],
        ];
    }

    private function tieneCalificaciones(Equivalencia $equivalencia): bool
    {
        return EstablecimientoComportamiento::query()
            ->whereIn('equivalencia_detalle_id', $equivalencia->detalles()->select('id'))
            ->exists();
    }
}
