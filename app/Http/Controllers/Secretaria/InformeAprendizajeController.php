<?php

namespace App\Http\Controllers\Secretaria;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\EsquemaCiclo;
use App\Http\Controllers\Controller;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Services\InformeAprendizaje;
use App\Services\LibretaAula;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InformeAprendizajeController extends Controller
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
        $periodo?->loadMissing('ciclos');
        $aulas = $periodo === null ? collect() : $this->libreta->aulasDelPeriodo($periodo);
        $ciclos = $periodo?->ciclos ?? collect();
        $aula = null;
        $ciclo = null;
        $tipo = (string) $request->input('tipo', '');
        $parcialNumero = $request->filled('parcial_numero') ? $request->integer('parcial_numero') : null;
        $informe = null;

        if ($request->filled('establecimiento_aula_id')) {
            $aula = $aulas->first(
                fn (EstablecimientoAula $disponible): bool => (int) $disponible->id === $request->integer('establecimiento_aula_id'),
            );
            abort_if(! $aula instanceof EstablecimientoAula, 404);
        }

        if ($request->filled('establecimiento_periodo_ciclo_id')) {
            $ciclo = $ciclos->first(
                fn (EstablecimientoPeriodoCiclo $disponible): bool => (int) $disponible->id === $request->integer('establecimiento_periodo_ciclo_id'),
            );
            abort_if(! $ciclo instanceof EstablecimientoPeriodoCiclo, 404);
        }

        $listo = $aula instanceof EstablecimientoAula
            && $ciclo instanceof EstablecimientoPeriodoCiclo
            && in_array($tipo, ['parcial', 'ciclo'], true)
            && ($tipo !== 'parcial' || $parcialNumero !== null);

        if ($listo && $tipo === 'parcial') {
            abort_if(
                $parcialNumero < 1 || $parcialNumero > (int) ($periodo?->numero_parciales ?? 0),
                404,
            );
        }

        if ($listo) {
            $informe = $this->informe->generar(
                $aula,
                $ciclo,
                $tipo === 'parcial' ? $parcialNumero : null,
            );
        }

        $numeroParciales = (int) ($periodo?->numero_parciales ?? 0);

        return view('secretaria.informes-aprendizaje', [
            'periodo' => $periodo,
            'aulas' => $aulas,
            'ciclos' => $ciclos,
            'aula' => $aula,
            'ciclo' => $ciclo,
            'tipo' => $tipo,
            'parcialNumero' => $parcialNumero,
            'parciales' => $numeroParciales < 1 ? [] : range(1, $numeroParciales),
            'informe' => $informe,
            'etiquetaCiclo' => $periodo?->esquema_ciclo?->nombrePorDefecto() ?: 'Ciclo',
            'etiquetaInformeCiclo' => match ($periodo?->esquema_ciclo) {
                EsquemaCiclo::Trimestres => 'Informe trimestral',
                EsquemaCiclo::Quimestres => 'Informe quimestral',
                default => 'Informe de ciclo',
            },
        ]);
    }
}
