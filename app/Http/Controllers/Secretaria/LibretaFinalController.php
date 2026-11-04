<?php

namespace App\Http\Controllers\Secretaria;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Http\Controllers\Controller;
use App\Models\EstablecimientoAula;
use App\Services\InformeAprendizaje;
use App\Services\LibretaAula;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibretaFinalController extends Controller
{
    public function __construct(
        private ActiveOferta $activeOferta,
        private ActivePeriodo $activePeriodo,
        private LibretaAula $libreta,
        private InformeAprendizaje $informe,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user !== null && $this->activeOferta->needsSelection($user)) {
            return redirect()->guest(route('oferta.select'));
        }

        $periodo = $this->activePeriodo->get($user);
        $aulas = $periodo === null ? collect() : $this->libreta->aulasDelPeriodo($periodo);
        $aula = null;
        $informe = null;

        if ($request->filled('establecimiento_aula_id')) {
            $aula = $aulas->first(
                fn (EstablecimientoAula $disponible): bool => (int) $disponible->id === $request->integer('establecimiento_aula_id'),
            );
            abort_if(! $aula instanceof EstablecimientoAula, 404);
            $informe = $this->informe->generarFinal($aula);
        }

        return view('secretaria.libreta-final', [
            'periodo' => $periodo,
            'aulas' => $aulas,
            'aula' => $aula,
            'informe' => $informe,
        ]);
    }
}
