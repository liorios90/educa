<?php

namespace App\View\Components;

use App\Auth\ActiveRole;
use App\Enums\Role;
use App\Models\User;
use App\Navigation\Navigation;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class Sidebar extends Component
{
    public function __construct(
        private Navigation $navigation,
        private ActiveRole $activeRole,
    ) {}

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $user->loadMissing(['establecimiento', 'roles']);
        $activeRole = $this->activeRole->get($user);
        $showSchoolBranding = $activeRole?->requiresEstablecimiento() === true;

        return view('components.sidebar', [
            'items' => $this->navigation->itemsFor($user),
            'user' => $user,
            'establecimiento' => $showSchoolBranding ? $user->establecimiento : null,
            'activeRole' => $activeRole,
            'canSwitchRole' => $user->roles->count() > 1,
            'isSistemas' => $activeRole === Role::Sistemas,
        ]);
    }
}
