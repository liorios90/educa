<?php

namespace App\Http\Controllers\Auth;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SelectActiveOfertaRequest;
use App\Models\EstablecimientoPeriodo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActiveOfertaController extends Controller
{
    public function __construct(
        private ActiveOferta $activeOferta,
        private ActivePeriodo $activePeriodo,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('establecimiento');

        $this->activeOferta->sync($user);
        $this->activePeriodo->sync($user);

        if (
            ! $this->activeOferta->canSwitch($user)
            && ! $this->activeOferta->needsSelection($user)
            && ! $this->activePeriodo->missing($user)
        ) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $ofertas = $this->activeOferta->available($user);
        $ofertaIds = $ofertas->modelKeys();
        $tienePeriodoActivo = $ofertaIds !== [] && EstablecimientoPeriodo::query()
            ->activo()
            ->where('establecimiento_id', $user->establecimiento_id)
            ->whereIn('establecimiento_modalidad_jornada_id', $ofertaIds)
            ->exists();

        return view('auth.select-oferta', [
            'establecimiento' => $user->establecimiento,
            'modalidades' => $this->activeOferta->modalidades($user),
            'activeOferta' => $this->activeOferta->get($user),
            'sinPeriodoActivo' => $ofertaIds !== [] && ! $tienePeriodoActivo,
        ]);
    }

    public function store(SelectActiveOfertaRequest $request): RedirectResponse
    {
        $this->activeOferta->set($request->oferta());
        $this->activePeriodo->set($request->periodo());

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
