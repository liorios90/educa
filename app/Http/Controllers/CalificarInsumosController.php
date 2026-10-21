<?php

namespace App\Http\Controllers;

use App\Auth\ActiveDocenteContexto;
use App\Auth\DocenteContexto;
use App\Enums\TipoCalificacion;
use App\Http\Requests\SelectDocenteContextoRequest;
use App\Http\Requests\StoreInsumoActividadRequest;
use App\Http\Requests\UpdateInsumoActividadRequest;
use App\Models\Alumno;
use App\Models\EstablecimientoInsumoActividad;
use App\Models\EstablecimientoInsumoActividadCalificacion;
use App\Models\User;
use App\Services\AsignacionesDocente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CalificarInsumosController extends Controller
{
    public function __construct(
        private AsignacionesDocente $asignaciones,
        private ActiveDocenteContexto $contexto,
    ) {}

    public function index(Request $request): View
    {
        return view('docente.calificar', $this->datosVista($request));
    }

    public function editar(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $actual = $this->contexto->get($user);
        $actividadId = $request->integer('actividad');
        $actividad = null;

        if ($actual !== null && $actividadId > 0) {
            $actividad = $this->asignaciones->actividadDelContexto($actual, $actividadId);

            if ($actividad === null) {
                abort(404);
            }
        }

        $datos = $this->datosVista($request, $actual);
        $notasGuardadas = $actividad === null
            ? []
            : $actividad->calificaciones
                ->mapWithKeys(fn (EstablecimientoInsumoActividadCalificacion $calificacion): array => [
                    (int) $calificacion->alumno_id => (string) $calificacion->nota,
                ])
                ->all();

        return view('docente.calificar-editar', [
            ...$datos,
            'insumosIngresados' => $actual === null ? [] : $this->asignaciones->actividadesAgrupadas($actual),
            'actividadEditando' => $actividad,
            'notasGuardadas' => $notasGuardadas,
            'alumnos' => $actividad === null
                ? $datos['alumnos']
                : $this->alumnosParaEditar($datos['alumnos'], $notasGuardadas),
        ]);
    }

    public function store(SelectDocenteContextoRequest $request): RedirectResponse
    {
        $this->contexto->set($request->contexto());

        if ($request->destino() === 'editar') {
            return redirect()->route('Docente.calificar.editar');
        }

        return redirect()->route('Docente.calificar.index');
    }

    public function storeActividad(StoreInsumoActividadRequest $request): RedirectResponse
    {
        $contexto = $request->contexto();
        $this->contexto->set($contexto);

        DB::transaction(function () use ($request, $contexto): void {
            $actividad = EstablecimientoInsumoActividad::query()->create([
                'establecimiento_aula_id' => $contexto->aula->id,
                'establecimiento_asignatura_id' => $contexto->asignatura->id,
                'establecimiento_periodo_ciclo_id' => $contexto->ciclo->id,
                'parcial_numero' => $contexto->parcialNumero,
                'tipo_insumo_detalle_id' => $request->detalle()->id,
                'empleado_id' => $request->empleadoId(),
                'nombre' => $request->nombre(),
            ]);

            foreach ($request->notas() as $alumnoId => $nota) {
                EstablecimientoInsumoActividadCalificacion::query()->create([
                    'establecimiento_insumo_actividad_id' => $actividad->id,
                    'alumno_id' => $alumnoId,
                    'nota' => $nota,
                ]);
            }
        });

        return redirect()
            ->route('Docente.calificar.index')
            ->with('status', 'calificaciones-grabadas');
    }

    public function updateActividad(UpdateInsumoActividadRequest $request, EstablecimientoInsumoActividad $actividad): RedirectResponse
    {
        $contexto = $request->contexto();
        $this->contexto->set($contexto);
        $actividad = $request->actividad();
        $notas = $request->notas();
        $alumnoIds = $request->alumnoIds();

        DB::transaction(function () use ($actividad, $notas, $alumnoIds, $request): void {
            $actividad->update([
                'nombre' => $request->nombre(),
            ]);

            if ($alumnoIds !== []) {
                EstablecimientoInsumoActividadCalificacion::query()
                    ->where('establecimiento_insumo_actividad_id', $actividad->id)
                    ->whereIn('alumno_id', $alumnoIds)
                    ->whereNotIn('alumno_id', array_keys($notas) === [] ? [0] : array_keys($notas))
                    ->delete();
            }

            foreach ($notas as $alumnoId => $nota) {
                EstablecimientoInsumoActividadCalificacion::query()->updateOrCreate(
                    [
                        'establecimiento_insumo_actividad_id' => $actividad->id,
                        'alumno_id' => $alumnoId,
                    ],
                    [
                        'nota' => $nota,
                    ],
                );
            }
        });

        return redirect()
            ->route('Docente.calificar.editar', ['actividad' => $actividad->id])
            ->with('status', 'calificaciones-grabadas');
    }

    /**
     * @return array{
     *     aulas: list<array<string, mixed>>,
     *     contexto: DocenteContexto|null,
     *     alumnos: Collection<int, Alumno>,
     *     tipoCalificacion: TipoCalificacion|null,
     *     valoresCualitativos: list<array{valor: string, etiqueta: string}>,
     *     notasGuardadas: array<int, string>,
     *     seleccion: array{aula: mixed, asignatura: mixed, ciclo: mixed, parcial: mixed}
     * }
     */
    private function datosVista(Request $request, ?DocenteContexto $actual = null): array
    {
        /** @var User $user */
        $user = $request->user();
        $actual ??= $this->contexto->get($user);

        return [
            'aulas' => $this->asignaciones->aulasSelector($user),
            'contexto' => $actual,
            'alumnos' => $actual === null ? collect() : $this->asignaciones->alumnos($actual),
            'tipoCalificacion' => $actual === null ? null : $this->asignaciones->tipoCalificacion($actual),
            'valoresCualitativos' => $actual === null ? [] : $this->asignaciones->valoresCualitativos($actual),
            'notasGuardadas' => [],
            'seleccion' => [
                'aula' => old('establecimiento_aula_id', $actual?->aula->id),
                'asignatura' => old('establecimiento_asignatura_id', $actual?->asignatura->id),
                'ciclo' => old('establecimiento_periodo_ciclo_id', $actual?->ciclo->id),
                'parcial' => old('parcial_numero', $actual?->parcialNumero),
            ],
        ];
    }

    /**
     * @param  Collection<int, Alumno>  $alumnos
     * @param  array<int, string>  $notasGuardadas
     * @return Collection<int, Alumno>
     */
    private function alumnosParaEditar(Collection $alumnos, array $notasGuardadas): Collection
    {
        return $alumnos
            ->sortBy(function (Alumno $alumno) use ($notasGuardadas): string {
                $nombre = Str::lower(trim(($alumno->persona->apellidos ?? '').' '.($alumno->persona->nombres ?? '')));
                $pendiente = isset($notasGuardadas[(int) $alumno->id]) ? '1' : '0';

                return $pendiente.'-'.$nombre;
            })
            ->values();
    }
}
