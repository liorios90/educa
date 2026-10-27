<?php

namespace App\Http\Controllers;

use App\Auth\ActiveAsistenciaContexto;
use App\Auth\ContextoAsistencia;
use App\Enums\ModoAsistencia;
use App\Http\Requests\StoreAsistenciaContextoRequest;
use App\Http\Requests\StoreAsistenciaDiariaRequest;
use App\Models\Alumno;
use App\Models\User;
use App\Services\AsignacionesDocente;
use App\Services\RegistroAsistenciaDiaria;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AsistenciaDiariaController extends Controller
{
    public function __construct(
        private AsignacionesDocente $asignaciones,
        private ActiveAsistenciaContexto $asistencia,
        private RegistroAsistenciaDiaria $registro,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $actual = $this->asistencia->get($user);

        return view('docente.asistencia', [
            'aulas' => $this->asignaciones->aulasSelector($user),
            'contexto' => $actual,
            'registro' => $actual === null
                ? null
                : $this->registro->presentar($actual, $this->fechaSolicitada($request), $this->alumnosDe($actual)),
            'seleccion' => [
                'aula' => old('establecimiento_aula_id', $actual?->aula->id),
                'asignatura' => old('establecimiento_asignatura_id', $actual?->asignatura?->id),
                'ciclo' => old('establecimiento_periodo_ciclo_id', $actual?->ciclo?->id),
                'parcial' => old('parcial_numero', $actual?->parcialNumero),
                'modo' => old('modo', $actual?->modo->value ?? ModoAsistencia::Asignatura->value),
            ],
        ]);
    }

    public function store(StoreAsistenciaContextoRequest $request): RedirectResponse
    {
        $contexto = $request->contexto();
        $this->asignaciones->recordarModoAsistencia($request->user(), $contexto->aula->id, $contexto->modo);
        $this->asistencia->set($contexto);

        return redirect()->route('Docente.asistencia.index');
    }

    public function registrar(StoreAsistenciaDiariaRequest $request): RedirectResponse
    {
        $contexto = $request->contexto();
        $fecha = $request->fecha();
        $this->asignaciones->recordarModoAsistencia($request->user(), $contexto->aula->id, $contexto->modo);
        $this->asistencia->set($contexto);
        $this->registro->guardar($contexto, $fecha, $request->filas());

        return redirect()
            ->route('Docente.asistencia.index', ['fecha' => $fecha->toDateString()])
            ->with('status', 'asistencia-registrada');
    }

    private function fechaSolicitada(Request $request): Carbon
    {
        $fecha = $request->query('fecha');

        if (! is_string($fecha) || ! Carbon::hasFormat($fecha, 'Y-m-d')) {
            return $this->registro->hoyLectivo();
        }

        return Carbon::createFromFormat('Y-m-d', $fecha)->startOfDay();
    }

    /**
     * @return Collection<int, Alumno>
     */
    private function alumnosDe(ContextoAsistencia $contexto): Collection
    {
        if ($contexto->modo->esJornada() || $contexto->asignatura === null) {
            return $this->asignaciones->alumnosDelAula($contexto->aula);
        }

        return $this->asignaciones->alumnosDe($contexto->aula, $contexto->asignatura);
    }
}
