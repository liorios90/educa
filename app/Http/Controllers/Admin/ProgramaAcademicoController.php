<?php

namespace App\Http\Controllers\Admin;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\TipoPrograma;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramaAcademicoRequest;
use App\Http\Requests\UpdateProgramaAcademicoAlumnosRequest;
use App\Http\Requests\UpdateProgramaAcademicoAsignaturasRequest;
use App\Http\Requests\UpdateProgramaAcademicoInscripcionesRequest;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAlumnoAsignatura;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoPrograma;
use App\Services\OrganizacionAcademica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramaAcademicoController extends Controller
{
    public function __construct(
        private ActiveOferta $activeOferta,
        private ActivePeriodo $activePeriodo,
        private OrganizacionAcademica $organizacion,
    ) {}

    public function index(Request $request): View|RedirectResponse
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

        $establecimiento = Establecimiento::query()->findOrFail($user->establecimiento_id);
        $programa = EstablecimientoPrograma::query()
            ->with(['membresias', 'asignaturas.asignaturaPropia', 'asignaturas.grado'])
            ->where('establecimiento_modalidad_jornada_id', $oferta->id)
            ->where('establecimiento_periodo_id', $periodo->id)
            ->where('tipo', TipoPrograma::BachilleratoInternacional)
            ->first();

        $matriculas = EstablecimientoMatricula::query()
            ->with(['alumno.persona', 'aula.establecimientoGrado.grado'])
            ->where('establecimiento_periodo_id', $periodo->id)
            ->whereHas('aula', fn ($query) => $query->where('establecimiento_modalidad_jornada_id', $oferta->id))
            ->get()
            ->sortBy(fn (EstablecimientoMatricula $matricula): string => trim(($matricula->alumno->persona->apellidos ?? '').' '.($matricula->alumno->persona->nombres ?? '')))
            ->values();

        $propias = EstablecimientoAsignatura::query()
            ->with(['asignaturaPropia', 'grado'])
            ->where('establecimiento_modalidad_jornada_id', $oferta->id)
            ->whereNotNull('asignatura_propia_id')
            ->get()
            ->sortBy(fn (EstablecimientoAsignatura $asignatura): string => ($asignatura->grado?->nombre ?? '').' '.$asignatura->nombre(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $inscripciones = $programa === null
            ? collect()
            : EstablecimientoAlumnoAsignatura::query()
                ->where('establecimiento_periodo_id', $periodo->id)
                ->whereIn('establecimiento_asignatura_id', $programa->asignaturas->pluck('id'))
                ->get()
                ->groupBy('establecimiento_asignatura_id');

        return view('admin.programas.index', [
            'establecimiento' => $establecimiento,
            'oferta' => $oferta,
            'periodo' => $periodo,
            'programa' => $programa,
            'matriculas' => $matriculas,
            'propias' => $propias,
            'inscripciones' => $inscripciones,
            'tipos' => TipoPrograma::cases(),
        ]);
    }

    public function store(StoreProgramaAcademicoRequest $request): RedirectResponse
    {
        $periodo = $request->periodo();
        $tipo = TipoPrograma::from($request->string('tipo')->toString());
        $this->organizacion->crearPrograma($periodo, $tipo, $request->string('nombre')->toString());

        return redirect()
            ->route('Admin.programas.index')
            ->with('status', 'programa-created');
    }

    public function updateAlumnos(UpdateProgramaAcademicoAlumnosRequest $request, EstablecimientoPrograma $programa): RedirectResponse
    {
        $this->exigirDelPeriodo($request, $programa);
        $this->organizacion->sincronizarAlumnosPrograma($programa, $request->ids('alumnos'));

        return redirect()
            ->route('Admin.programas.index')
            ->with('status', 'programa-updated');
    }

    public function updateAsignaturas(UpdateProgramaAcademicoAsignaturasRequest $request, EstablecimientoPrograma $programa): RedirectResponse
    {
        $this->exigirDelPeriodo($request, $programa);
        $this->organizacion->sincronizarAsignaturasPrograma($programa, $request->ids('asignaturas'));

        return redirect()
            ->route('Admin.programas.index')
            ->with('status', 'programa-updated');
    }

    public function updateInscripciones(
        UpdateProgramaAcademicoInscripcionesRequest $request,
        EstablecimientoPrograma $programa,
        EstablecimientoAsignatura $asignatura,
    ): RedirectResponse {
        $this->exigirDelPeriodo($request, $programa);
        abort_unless((int) $asignatura->establecimiento_programa_id === (int) $programa->id, 404);
        $this->organizacion->sincronizarInscripciones($programa->periodo, $asignatura, $request->ids('alumnos'));

        return redirect()
            ->route('Admin.programas.index')
            ->with('status', 'programa-updated');
    }

    private function exigirDelPeriodo(Request $request, EstablecimientoPrograma $programa): void
    {
        $periodo = $this->activePeriodo->get($request->user());

        abort_unless(
            (int) $request->user()?->establecimiento_id === (int) $programa->establecimiento_id
            && $periodo !== null
            && (int) $periodo->id === (int) $programa->establecimiento_periodo_id,
            404,
        );
    }
}
