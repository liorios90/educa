<?php

namespace App\Http\Controllers\Auth;

use App\Auth\ActiveRole;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SelectActiveRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActiveRoleController extends Controller
{
    public function __construct(private ActiveRole $activeRole) {}

    public function create(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $roles = $this->activeRole->assigned($user);

        if ($roles->count() <= 1) {
            if ($roles->count() === 1) {
                $this->activeRole->set($roles->first());
            }

            return redirect()->intended(route('dashboard', absolute: false));
        }

        return view('auth.select-role', [
            'roles' => $roles,
            'activeRole' => $this->activeRole->get($user),
        ]);
    }

    public function store(SelectActiveRoleRequest $request): RedirectResponse
    {
        /** @var Role $role */
        $role = $request->enum('role', Role::class);
        $this->activeRole->set($role);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
