<?php

namespace App\Http\Controllers;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Http\Requests\StoreEstablecimientoAulaRequest;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoGrado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EstablecimientoAulaController extends Controller
{
    public function __construct(
        private ActiveOferta $activeOferta,
        private ActivePeriodo $activePeriodo,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $establecimiento = $this->establecimiento($request);
        $user = $request->user();

        if ($user !== null && $this->activeOferta->needsSelection($user)) {
            return redirect()->guest(route('oferta.select'));
        }

        $oferta = $this->activeOferta->get($user);
        $periodo = $this->activePeriodo->get($user);

        $grados = $oferta === null
            ? collect()
            : EstablecimientoGrado::query()
                ->with('grado')
                ->where('establecimiento_id', $establecimiento->id)
                ->where('establecimiento_modalidad_jornada_id', $oferta->id)
                ->orderBy('id')
                ->get();

        $aulas = $periodo === null
            ? collect()
            : EstablecimientoAula::query()
                ->with('establecimientoGrado.grado')
                ->where('establecimiento_id', $establecimiento->id)
                ->where('establecimiento_modalidad_jornada_id', $oferta->id)
                ->where('establecimiento_periodo_id', $periodo->id)
                ->orderBy('id')
                ->get();

        return view('admin.aulas.index', [
            'establecimiento' => $establecimiento,
            'oferta' => $oferta,
            'periodo' => $periodo,
            'grados' => $grados,
            'aulas' => $aulas,
        ]);
    }

    public function store(StoreEstablecimientoAulaRequest $request): RedirectResponse
    {
        $establecimiento = $this->establecimiento($request);
        $oferta = $request->oferta();
        $periodo = $request->periodo();

        abort_if($oferta === null || $periodo === null, 403);

        $establecimiento->aulas()->create([
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $request->integer('establecimiento_grado_id'),
            'paralelo' => $request->string('paralelo')->toString(),
        ]);

        return redirect()
            ->route('Admin.aulas')
            ->with('status', 'aula-created');
    }

    public function destroy(Request $request, EstablecimientoAula $aula): RedirectResponse
    {
        $establecimiento = $this->establecimiento($request);
        $oferta = $this->activeOferta->get($request->user());
        $periodo = $this->activePeriodo->get($request->user());

        abort_unless(
            (int) $aula->establecimiento_id === (int) $establecimiento->id
            && $oferta !== null
            && $periodo !== null
            && (int) $aula->establecimiento_modalidad_jornada_id === (int) $oferta->id
            && (int) $aula->establecimiento_periodo_id === (int) $periodo->id,
            404,
        );

        $aula->delete();

        return redirect()
            ->route('Admin.aulas')
            ->with('status', 'aula-deleted');
    }

    private function establecimiento(Request $request): Establecimiento
    {
        $establecimientoId = $request->user()?->establecimiento_id;

        abort_if($establecimientoId === null, 403);

        return Establecimiento::query()->findOrFail($establecimientoId);
    }
}
