<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Services\LibretaAula;
use App\Services\LibretaFamiliar;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibretaFamiliarController extends Controller
{
    public function __construct(
        private LibretaFamiliar $familiar,
        private LibretaAula $libreta,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $alumnos = $this->familiar->alumnosDe($user);
        $alumno = $this->familiar->seleccionar($request, $alumnos);
        $matricula = $alumno === null ? null : $this->familiar->matriculaActiva($alumno, $user);
        $aula = $matricula?->aula;
        $libreta = ($aula !== null && $alumno !== null)
            ? $this->libreta->paraAlumno($aula, (int) $alumno->id)
            : null;

        return view('notas.index', [
            'alumnos' => $alumnos,
            'alumno' => $alumno,
            'aula' => $aula,
            'periodo' => $matricula?->periodo ?? $aula?->periodo,
            'libreta' => $libreta,
            'puedeElegirAlumno' => $user->hasRole(Role::Padre) && $alumnos->count() > 1,
        ]);
    }
}
