<?php

namespace App\View\Components;

use App\Auth\ActiveRole;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class UserToolbar extends Component
{
    public function __construct(private ActiveRole $activeRole) {}

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

        return view('components.user-toolbar', [
            'user' => $user,
            'roleLabel' => $roleLabel,
            'canSwitchRole' => $user->roles->count() > 1,
        ]);
    }
}
