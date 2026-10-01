<?php

namespace App\View\Components;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Auth\ActiveRole;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class UserToolbar extends Component
{
    public function __construct(
        private ActiveRole $activeRole,
        private ActiveOferta $activeOferta,
        private ActivePeriodo $activePeriodo,
    ) {}

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $user->loadMissing('roles');

        $activeRole = $this->activeRole->get($user);
        $fallbackRole = $user->roles->first();

        $roleLabel = $activeRole?->label()
            ?? ($fallbackRole
                ? (Role::tryFrom($fallbackRole->name)?->label() ?? $fallbackRole->name)
                : 'Sin rol');

        $oferta = $this->activeOferta->get($user);
        $periodo = $this->activePeriodo->get($user);

        return view('components.user-toolbar', [
            'user' => $user,
            'roleLabel' => $roleLabel,
            'ofertaLabel' => $oferta?->etiqueta(),
            'periodoLabel' => $periodo?->nombre,
            'canSwitchRole' => $user->roles->count() > 1,
            'canSwitchOferta' => $this->activeOferta->canSwitch($user),
        ]);
    }
}
