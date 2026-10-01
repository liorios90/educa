<?php

namespace App\Auth;

use App\Enums\Role;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\User;
use Illuminate\Support\Collection;

class ActiveOferta
{
    public const SESSION_KEY = 'active_oferta';

    public function __construct(private ActiveRole $activeRole) {}

    public function appliesTo(?User $user): bool
    {
        return $user instanceof User
            && $user->establecimiento_id !== null
            && $this->activeRole->get($user) === Role::Admin;
    }

    /**
     * @return Collection<int, EstablecimientoModalidadJornada>
     */
    public function available(User $user): Collection
    {
        if ($user->establecimiento_id === null) {
            return collect();
        }

        return EstablecimientoModalidadJornada::query()
            ->whereHas(
                'establecimientoModalidad',
                fn ($query) => $query->where('establecimiento_id', $user->establecimiento_id),
            )
            ->with(['jornada', 'establecimientoModalidad.modalidad'])
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, EstablecimientoModalidad>
     */
    public function modalidades(User $user): Collection
    {
        if ($user->establecimiento_id === null) {
            return collect();
        }

        return EstablecimientoModalidad::query()
            ->where('establecimiento_id', $user->establecimiento_id)
            ->whereHas('establecimientoJornadas')
            ->with([
                'modalidad',
                'establecimientoJornadas' => fn ($query) => $query
                    ->with('jornada')
                    ->orderBy('id'),
            ])
            ->orderBy('id')
            ->get();
    }

    public function get(?User $user = null): ?EstablecimientoModalidadJornada
    {
        $user ??= auth()->user();

        if (! $user instanceof User || ! $this->appliesTo($user)) {
            return null;
        }

        $id = session(self::SESSION_KEY);

        if (! is_numeric($id)) {
            return null;
        }

        return $this->available($user)->firstWhere('id', (int) $id);
    }

    public function set(EstablecimientoModalidadJornada $oferta): void
    {
        session([self::SESSION_KEY => $oferta->id]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function needsSelection(User $user): bool
    {
        if (! $this->appliesTo($user)) {
            return false;
        }

        if ($this->available($user)->isEmpty()) {
            return false;
        }

        return $this->get($user) === null;
    }

    public function sync(User $user): void
    {
        if (! $this->appliesTo($user)) {
            $this->clear();

            return;
        }

        $ofertas = $this->available($user);

        if ($ofertas->isEmpty()) {
            $this->clear();

            return;
        }

        if ($this->get($user) !== null) {
            return;
        }

        if ($ofertas->count() === 1) {
            $this->set($ofertas->first());
        }
    }

    public function canSwitch(User $user): bool
    {
        return $this->appliesTo($user) && $this->available($user)->count() > 1;
    }
}
