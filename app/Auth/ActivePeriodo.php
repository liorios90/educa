<?php

namespace App\Auth;

use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use App\Models\User;

class ActivePeriodo
{
    public const SESSION_KEY = 'active_periodo';

    public function __construct(
        private ActiveOferta $activeOferta,
        private AuthContext $authContext,
    ) {}

    public function forOferta(EstablecimientoModalidadJornada $oferta): ?EstablecimientoPeriodo
    {
        $oferta->loadMissing('establecimientoModalidad');

        $establecimientoId = $oferta->establecimientoModalidad?->establecimiento_id;

        if ($establecimientoId === null) {
            return null;
        }

        return EstablecimientoPeriodo::query()
            ->activo()
            ->where('establecimiento_modalidad_jornada_id', $oferta->id)
            ->where('establecimiento_id', $establecimientoId)
            ->first();
    }

    public function get(?User $user = null): ?EstablecimientoPeriodo
    {
        $user ??= auth()->user();

        if (! $user instanceof User || ! $this->activeOferta->appliesTo($user)) {
            return null;
        }

        $oferta = $this->activeOferta->get($user);
        $id = session(self::SESSION_KEY);

        if ($oferta === null || ! is_numeric($id)) {
            return null;
        }

        return EstablecimientoPeriodo::query()
            ->activo()
            ->whereKey((int) $id)
            ->where('establecimiento_modalidad_jornada_id', $oferta->id)
            ->where('establecimiento_id', $user->establecimiento_id)
            ->first();
    }

    public function set(EstablecimientoPeriodo $periodo): void
    {
        session([self::SESSION_KEY => $periodo->id]);
        $this->authContext->rememberPeriodo($periodo);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
        $this->authContext->rememberPeriodo(null);
    }

    public function missing(User $user): bool
    {
        return $this->activeOferta->appliesTo($user)
            && $this->activeOferta->get($user) !== null
            && $this->get($user) === null;
    }
}
