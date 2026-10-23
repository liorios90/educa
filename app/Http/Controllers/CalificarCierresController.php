<?php

namespace App\Http\Controllers;

use App\Auth\ActiveDocenteContexto;
use App\Auth\DocenteContexto;
use App\Enums\TipoCalificacion;
use App\Enums\TipoCierreEvaluacion;
use App\Http\Requests\SelectDocenteCierreContextoRequest;
use App\Http\Requests\StoreCierreActividadRequest;
use App\Models\Alumno;
use App\Models\EstablecimientoCierreActividad;
use App\Models\EstablecimientoCierreActividadCalificacion;
use App\Models\User;
use App\Services\AsignacionesDocente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CalificarCierresController extends Controller
{
    public function __construct(
        private AsignacionesDocente $asignaciones,
        private ActiveDocenteContexto $contexto,
    ) {}

    public function index(Request $request, string $tipo): View
    {
        return view('docente.calificar-cierre', $this->datosVista($request, $this->tipo($tipo)));
    }

    public function store(SelectDocenteCierreContextoRequest $request, string $tipo): RedirectResponse
    {
        $this->contexto->set($request->contexto());

        return redirect()->route($this->tipo($tipo)->route('index'));
    }

    public function storeActividad(StoreCierreActividadRequest $request, string $tipo): RedirectResponse
    {
        $cierre = $this->tipo($tipo);
        $contexto = $request->contexto();
        $this->contexto->set($contexto);
        $notas = $request->notas();
        $alumnoIds = $request->alumnoIds();

        DB::transaction(function () use ($request, $contexto, $cierre, $notas, $alumnoIds): void {
            $llaves = [
                'establecimiento_aula_id' => $contexto->aula->id,
                'establecimiento_asignatura_id' => $contexto->asignatura->id,
                'tipo' => $cierre,
            ];
            $valores = [
                'empleado_id' => $request->empleadoId(),
                'nombre' => $request->nombre(),
            ];

            if ($cierre->esFinal()) {
                $valores['establecimiento_periodo_ciclo_id'] = $contexto->ciclo->id;
            } else {
                $llaves['establecimiento_periodo_ciclo_id'] = $contexto->ciclo->id;
            }

            $actividad = EstablecimientoCierreActividad::query()->updateOrCreate($llaves, $valores);

            if ($alumnoIds !== []) {
                EstablecimientoCierreActividadCalificacion::query()
                    ->where('establecimiento_cierre_actividad_id', $actividad->id)
                    ->whereIn('alumno_id', $alumnoIds)
                    ->whereNotIn('alumno_id', array_keys($notas) === [] ? [0] : array_keys($notas))
                    ->delete();
            }

            foreach ($notas as $alumnoId => $nota) {
                EstablecimientoCierreActividadCalificacion::query()->updateOrCreate(
                    [
                        'establecimiento_cierre_actividad_id' => $actividad->id,
                        'alumno_id' => $alumnoId,
                    ],
                    [
                        'nota' => $nota,
                    ],
                );
            }
        });

        return redirect()
            ->route($cierre->route('index'))
            ->with('status', 'calificaciones-grabadas');
    }

    /**
     * @return array{
     *     tipo: TipoCierreEvaluacion,
     *     aulas: list<array<string, mixed>>,
     *     contexto: DocenteContexto|null,
     *     actividad: EstablecimientoCierreActividad|null,
     *     alumnos: Collection<int, Alumno>,
     *     tipoCalificacion: TipoCalificacion|null,
     *     valoresCualitativos: list<array{valor: string, etiqueta: string}>,
     *     notasGuardadas: array<int, string>,
     *     cicloTieneCierre: bool,
     *     seleccion: array{aula: mixed, asignatura: mixed, ciclo: mixed, parcial: mixed}
     * }
     */
    private function datosVista(Request $request, TipoCierreEvaluacion $tipo): array
    {
        /** @var User $user */
        $user = $request->user();
        $actual = $this->contextoCierre($user);
        $actividad = $actual === null ? null : $this->asignaciones->cierreDelContexto($actual, $tipo);
        $notasGuardadas = $actividad === null
            ? []
            : $actividad->calificaciones
                ->mapWithKeys(fn (EstablecimientoCierreActividadCalificacion $calificacion): array => [
                    (int) $calificacion->alumno_id => (string) $calificacion->nota,
                ])
                ->all();
        $alumnos = $actual === null ? collect() : $this->asignaciones->alumnos($actual);

        return [
            'tipo' => $tipo,
            'aulas' => $this->asignaciones->aulasSelector($user),
            'contexto' => $actual,
            'actividad' => $actividad,
            'alumnos' => $actividad === null
                ? $alumnos
                : $this->alumnosParaEditar($alumnos, $notasGuardadas),
            'tipoCalificacion' => $actual === null ? null : $this->asignaciones->tipoCalificacion($actual),
            'valoresCualitativos' => $actual === null ? [] : $this->asignaciones->valoresCualitativos($actual),
            'notasGuardadas' => $notasGuardadas,
            'cicloTieneCierre' => $actual !== null && $this->asignaciones->cicloTieneCierre($actual, $tipo),
            'seleccion' => [
                'aula' => old('establecimiento_aula_id', $actual?->aula->id),
                'asignatura' => old('establecimiento_asignatura_id', $actual?->asignatura->id),
                'ciclo' => old('establecimiento_periodo_ciclo_id', $actual?->ciclo->id),
                'parcial' => '',
            ],
        ];
    }

    private function contextoCierre(User $user): ?DocenteContexto
    {
        $actual = $this->contexto->get($user);

        if ($actual === null) {
            return null;
        }

        return $this->asignaciones->contexto(
            $user,
            (int) $actual->aula->id,
            (int) $actual->asignatura->id,
            (int) $actual->ciclo->id,
            0,
        );
    }

    private function tipo(string $tipo): TipoCierreEvaluacion
    {
        return TipoCierreEvaluacion::fromRuta($tipo);
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
