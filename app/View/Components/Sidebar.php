<?php

namespace App\View\Components;

use App\Auth\ActiveRole;
use App\Enums\Role;
use App\Models\User;
use App\Navigation\Navigation;
use App\Services\BandejaNotificaciones;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class Sidebar extends Component
{
    public function __construct(
        private Navigation $navigation,
        private ActiveRole $activeRole,
        private BandejaNotificaciones $bandeja,
    ) {}

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $user->loadMissing('establecimiento');
        $activeRole = $this->activeRole->get($user);
        $showSchoolBranding = $activeRole?->requiresEstablecimiento() === true;

        return view('components.sidebar', [
            'items' => $this->navigation->itemsFor($user),
            'establecimiento' => $showSchoolBranding ? $user->establecimiento : null,
            'isSistemas' => $activeRole === Role::Sistemas,
            'notificacionesNoLeidas' => $this->bandeja->noLeidas($user),
        ]);
    }
}
