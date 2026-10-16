<?php

namespace App\Http\Controllers\Admin;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyMatriculaAcademicaRequest;
use App\Http\Requests\StoreGrupoAsignaturaRequest;
use App\Http\Requests\StoreMatriculaAcademicaRequest;
use App\Http\Requests\UpdateGrupoAsignaturaAlumnosRequest;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoGrupo;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use App\Models\Persona;
use App\Services\OrganizacionAcademica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class GrupoAsignaturaController extends Controller
{
    public function __construct(
        private ActiveOferta $activeOferta,
        private ActivePeriodo $activePeriodo,
        private OrganizacionAcademica $organizacion,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $contexto = $this->contexto($request);

        if ($contexto instanceof RedirectResponse) {
            return $contexto;
        }

        [$establecimiento, $oferta, $periodo] = $contexto;

        $aulas = $this->ordenarAulas(EstablecimientoAula::query()
            ->with('establecimientoGrado.grado.subnivel')
            ->where('establecimiento_id', $establecimiento->id)
            ->where('establecimiento_modalidad_jornada_id', $oferta->id)
            ->where('establecimiento_periodo_id', $periodo->id)
            ->get());

        $matriculas = EstablecimientoMatricula::query()
            ->with(['alumno.persona', 'aula.establecimientoGrado.grado'])
            ->where('establecimiento_periodo_id', $periodo->id)
            ->whereIn('establecimiento_aula_id', $aulas->pluck('id'))
            ->get();

        $alumnos = Alumno::query()
            ->with('persona')
            ->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))
            ->get()
            ->sortBy(fn (Alumno $alumno): string => trim(($alumno->persona->apellidos ?? '').' '.($alumno->persona->nombres ?? '')))
            ->values();

        $asignaturas = EstablecimientoAsignatura::query()
            ->with(['asignatura', 'asignaturaPropia', 'grado'])
            ->where('establecimiento_modalidad_jornada_id', $oferta->id)
            ->get()
            ->sortBy(fn (EstablecimientoAsignatura $asignatura): string => ($asignatura->grado?->nombre ?? '').' '.$asignatura->nombre(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $docentes = Empleado::query()
            ->with('persona')
            ->where('activo', 1)
            ->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))
            ->get()
            ->sortBy(fn (Empleado $docente): string => trim(($docente->persona->apellidos ?? '').' '.($docente->persona->nombres ?? '')))
            ->values();

        $grupos = EstablecimientoGrupo::query()
            ->with(['asignatura.asignatura', 'asignatura.asignaturaPropia', 'asignatura.grado', 'docente.persona', 'miembros.alumno.persona'])
            ->where('establecimiento_periodo_id', $periodo->id)
            ->orderBy('nombre')
            ->get();

        $matriculados = $matriculas->pluck('alumno_id')->map(fn (mixed $id): int => (int) $id)->all();
        $alumnosDisponibles = $alumnos
            ->reject(fn (Alumno $alumno): bool => in_array((int) $alumno->id, $matriculados, true))
            ->values();

        return view('admin.grupos-asignatura.index', [
            'establecimiento' => $establecimiento,
            'oferta' => $oferta,
            'periodo' => $periodo,
            'aulas' => $aulas,
            'aulasMatricula' => $this->aulasConMatriculas($aulas, $matriculas),
            'matriculas' => $matriculas,
            'alumnos' => $alumnos,
            'alumnosDisponibles' => $alumnosDisponibles,
            'gradosAsignatura' => $asignaturas
                ->mapWithKeys(fn (EstablecimientoAsignatura $asignatura): array => [
                    (string) $asignatura->id => (int) ($asignatura->grado_id ?? 0),
                ])
                ->all(),
            'etiquetasAsignatura' => $asignaturas
                ->mapWithKeys(fn (EstablecimientoAsignatura $asignatura): array => [
                    (string) $asignatura->id => trim(($asignatura->grado?->nombre ?? '').' · '.$asignatura->nombre()),
                ])
                ->all(),
            'ocupadosPorAsignatura' => $this->ocupadosPorAsignatura($grupos),
            'asignaturas' => $asignaturas,
            'docentes' => $docentes,
            'grupos' => $grupos,
            'siguienteNumero' => $this->organizacion->siguienteNumeroMatricula((int) $establecimiento->id, (int) $periodo->id),
            'fechaMatricula' => old('fecha', now()->toDateString()),
        ]);
    }

    public function storeMatricula(StoreMatriculaAcademicaRequest $request): RedirectResponse
    {
        $periodo = $request->periodo();
        $oferta = $request->oferta();
        $aula = EstablecimientoAula::query()
            ->where('establecimiento_id', $request->user()->establecimiento_id)
            ->where('establecimiento_periodo_id', $periodo?->id)
            ->where('establecimiento_modalidad_jornada_id', $oferta?->id)
            ->findOrFail($request->integer('establecimiento_aula_id'));
        $alumnoIds = $request->ids('alumnos');
        $alumnos = Alumno::query()
            ->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $request->user()->establecimiento_id))
            ->whereIn('id', $alumnoIds)
            ->get();

        abort_unless($alumnos->count() === count($alumnoIds), 404);

        $creadas = $this->organizacion->matricularVarios($aula, $alumnos->all(), $request->date('fecha')->toDateString());

        return redirect()
            ->route('Admin.grupos-asignatura.index')
            ->with('status', 'matricula-created')
            ->with('matriculas_creadas', $creadas);
    }

    public function destroyMatricula(DestroyMatriculaAcademicaRequest $request, EstablecimientoMatricula $matricula): RedirectResponse
    {
        abort_unless(
            (int) $matricula->establecimiento_id === (int) $request->user()?->establecimiento_id
            && (int) $matricula->establecimiento_periodo_id === (int) $request->periodo()?->id,
            404,
        );

        $this->organizacion->desmatricular($matricula);

        return redirect()
            ->route('Admin.grupos-asignatura.index')
            ->with('status', 'matricula-deleted');
    }

    public function store(StoreGrupoAsignaturaRequest $request): RedirectResponse
    {
        $periodo = $request->periodo();
        $oferta = $request->oferta();
        $asignatura = EstablecimientoAsignatura::query()
            ->where('establecimiento_id', $request->user()->establecimiento_id)
            ->where('establecimiento_modalidad_jornada_id', $oferta?->id)
            ->findOrFail($request->integer('establecimiento_asignatura_id'));
        $docente = null;

        if ($request->filled('empleado_id')) {
            $docente = Empleado::query()
                ->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $request->user()->establecimiento_id))
                ->findOrFail($request->integer('empleado_id'));
        }

        $grupo = $this->organizacion->crearGrupo($periodo, $asignatura, $request->string('nombre')->toString(), $docente);
        $alumnoIds = $request->ids('alumnos');

        if ($alumnoIds !== []) {
            $this->organizacion->sincronizarAlumnosGrupo($grupo, $alumnoIds);
        }

        return redirect()
            ->route('Admin.grupos-asignatura.index')
            ->with('status', 'grupo-created');
    }

    public function updateAlumnos(UpdateGrupoAsignaturaAlumnosRequest $request, EstablecimientoGrupo $grupo): RedirectResponse
    {
        $this->exigirDelPeriodo($request, (int) $grupo->establecimiento_id, (int) $grupo->establecimiento_periodo_id);
        $this->organizacion->sincronizarAlumnosGrupo($grupo, $request->ids('alumnos'));

        return redirect()
            ->route('Admin.grupos-asignatura.index')
            ->with('status', 'grupo-updated');
    }

    public function destroy(Request $request, EstablecimientoGrupo $grupo): RedirectResponse
    {
        abort_unless(
            $request->user() !== null
            && $request->user()->hasAnyRole([Role::Admin->value, Role::Secretaria->value]),
            403,
        );
        $this->exigirDelPeriodo($request, (int) $grupo->establecimiento_id, (int) $grupo->establecimiento_periodo_id);
        $grupo->delete();

        return redirect()
            ->route('Admin.grupos-asignatura.index')
            ->with('status', 'grupo-deleted');
    }

    /**
     * @return RedirectResponse|array{0: Establecimiento, 1: EstablecimientoModalidadJornada, 2: EstablecimientoPeriodo}
     */
    private function contexto(Request $request): RedirectResponse|array
    {
        $user = $request->user();
        abort_if($user?->establecimiento_id === null, 403);

        if ($this->activeOferta->needsSelection($user)) {
            return redirect()->guest(route('oferta.select'));
        }

        $oferta = $this->activeOferta->get($user);
        $periodo = $this->activePeriodo->get($user);

        if ($oferta === null || $periodo === null) {
            return redirect()->guest(route('oferta.select'));
        }

        return [
            Establecimiento::query()->findOrFail($user->establecimiento_id),
            $oferta,
            $periodo,
        ];
    }

    private function exigirDelPeriodo(Request $request, int $establecimientoId, int $periodoId): void
    {
        $periodo = $this->activePeriodo->get($request->user());

        abort_unless(
            (int) $request->user()?->establecimiento_id === $establecimientoId
            && $periodo !== null
            && (int) $periodo->id === $periodoId,
            404,
        );
    }

    /**
     * @param  Collection<int, EstablecimientoAula>  $aulas
     * @return Collection<int, EstablecimientoAula>
     */
    private function ordenarAulas(Collection $aulas): Collection
    {
        return $aulas
            ->sortBy(fn (EstablecimientoAula $aula): string => sprintf(
                '%05d-%05d-%05d-%s-%05d',
                (int) ($aula->establecimientoGrado?->grado?->subnivel?->nivel_id ?? 99999),
                (int) ($aula->establecimientoGrado?->subnivel_id ?? 99999),
                (int) ($aula->establecimientoGrado?->grado_id ?? 99999),
                $aula->paralelo,
                (int) $aula->id,
            ))
            ->values();
    }

    /**
     * @param  Collection<int, EstablecimientoGrupo>  $grupos
     * @return array<string, array<string, array{grupoId: int, nombre: string}>>
     */
    private function ocupadosPorAsignatura(Collection $grupos): array
    {
        $ocupados = [];

        foreach ($grupos as $grupo) {
            foreach ($grupo->miembros as $miembro) {
                $ocupados[(string) $grupo->establecimiento_asignatura_id][(string) $miembro->alumno_id] = [
                    'grupoId' => (int) $grupo->id,
                    'nombre' => $grupo->nombre,
                ];
            }
        }

        return $ocupados;
    }

    /**
     * @param  Collection<int, EstablecimientoAula>  $aulas
     * @param  Collection<int, EstablecimientoMatricula>  $matriculas
     * @return list<array{id: int, etiqueta: string, gradoId: int, alumnos: list<array{id: int, matriculaId: int, nombre: string, apellidos: string, numero: string, quitar: string}>}>
     */
    private function aulasConMatriculas(Collection $aulas, Collection $matriculas): array
    {
        $porAula = $matriculas->groupBy('establecimiento_aula_id');

        return $aulas->map(function (EstablecimientoAula $aula) use ($porAula): array {
            $alumnos = $porAula->get($aula->id, collect())
                ->sortBy(fn (EstablecimientoMatricula $matricula): string => $this->nombrePersona($matricula->alumno->persona))
                ->map(fn (EstablecimientoMatricula $matricula): array => [
                    'id' => (int) $matricula->alumno_id,
                    'matriculaId' => (int) $matricula->id,
                    'nombre' => $this->nombrePersona($matricula->alumno->persona),
                    'apellidos' => (string) ($matricula->alumno->persona->apellidos ?? ''),
                    'numero' => (string) $matricula->numero,
                    'quitar' => route('Admin.grupos-asignatura.matriculas.destroy', $matricula),
                ])
                ->values()
                ->all();

            return [
                'id' => (int) $aula->id,
                'etiqueta' => $aula->etiqueta(),
                'gradoId' => (int) ($aula->establecimientoGrado?->grado_id ?? 0),
                'alumnos' => $alumnos,
            ];
        })->all();
    }

    private function nombrePersona(?Persona $persona): string
    {
        return trim(($persona?->apellidos ?? '').' '.($persona?->nombres ?? ''));
    }
}
