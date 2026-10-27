<?php

namespace App\Http\Controllers;

use App\Auth\ActiveComportamientoContexto;
use App\Http\Requests\StoreComportamientoContextoRequest;
use App\Http\Requests\StoreComportamientoRequest;
use App\Models\User;
use App\Services\AsignacionesDocente;
use App\Services\RegistroComportamiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComportamientoController extends Controller
{
    public function __construct(
        private AsignacionesDocente $asignaciones,
        private ActiveComportamientoContexto $comportamiento,
        private RegistroComportamiento $registro,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $actual = $this->comportamiento->get($user);
        $alumnos = $actual === null ? collect() : $this->asignaciones->alumnosDelAula($actual->aula);

        return view('docente.comportamiento', [
            'aulas' => $this->asignaciones->aulasSelector($user),
            'contexto' => $actual,
            'registro' => $actual === null ? null : $this->registro->presentar($actual, $alumnos),
            'seleccion' => [
                'aula' => old('establecimiento_aula_id', $actual?->aula->id),
                'asignatura' => '',
                'ciclo' => old('establecimiento_periodo_ciclo_id', $actual?->ciclo->id),
                'parcial' => '',
            ],
        ]);
    }

    public function store(StoreComportamientoContextoRequest $request): RedirectResponse
    {
        $this->comportamiento->set($request->contexto());

        return redirect()->route('Docente.comportamiento.index');
    }

    public function registrar(StoreComportamientoRequest $request): RedirectResponse
    {
        $contexto = $request->contexto();
        $this->comportamiento->set($contexto);
        $this->registro->guardar($contexto, $request->filas());

        return redirect()
            ->route('Docente.comportamiento.index')
            ->with('status', 'comportamiento-registrado');
    }
}
