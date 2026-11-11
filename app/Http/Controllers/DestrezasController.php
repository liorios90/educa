<?php

namespace App\Http\Controllers;

use App\Auth\ActiveDestrezaContexto;
use App\Http\Requests\StoreDestrezaCalificacionRequest;
use App\Http\Requests\StoreDestrezaContextoRequest;
use App\Models\User;
use App\Services\AsignacionesDocente;
use App\Services\RegistroDestrezas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DestrezasController extends Controller
{
    public function __construct(
        private AsignacionesDocente $asignaciones,
        private ActiveDestrezaContexto $destreza,
        private RegistroDestrezas $registro,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $actual = $this->destreza->get($user);
        $alumnos = $actual === null
            ? collect()
            : $this->asignaciones->alumnosDe($actual->aula, $actual->asignatura);

        return view('docente.destrezas', [
            'aulas' => $this->asignaciones->aulasSelector($user),
            'contexto' => $actual,
            'registro' => $actual === null ? null : $this->registro->presentar($actual, $alumnos),
            'seleccion' => [
                'aula' => old('establecimiento_aula_id', $actual?->aula->id),
                'asignatura' => old('establecimiento_asignatura_id', $actual?->asignatura->id),
                'ciclo' => old('establecimiento_periodo_ciclo_id', $actual?->ciclo->id),
                'parcial' => '',
            ],
        ]);
    }

    public function store(StoreDestrezaContextoRequest $request): RedirectResponse
    {
        $this->destreza->set($request->contexto());

        return redirect()->route('Docente.destrezas.index');
    }

    public function registrar(StoreDestrezaCalificacionRequest $request): RedirectResponse
    {
        $contexto = $request->contexto();
        $this->destreza->set($contexto);
        $this->registro->guardar($contexto, $request->filas());

        return redirect()
            ->route('Docente.destrezas.index')
            ->with('status', 'destrezas-registradas');
    }
}
