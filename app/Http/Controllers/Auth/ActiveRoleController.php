<?php

namespace App\Http\Controllers\Auth;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
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
    public function __construct(
        private ActiveRole $activeRole,
        private ActiveOferta $activeOferta,
        private ActivePeriodo $activePeriodo,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $roles = $this->activeRole->assigned($user);

        if ($roles->count() <= 1) {
            if ($roles->count() === 1) {
                $this->activeRole->set($roles->first());
            }

            return $this->redirectAfterRole($user);
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
        $this->activeOferta->clear();
        $this->activePeriodo->clear();
        $this->activeRole->set($role);

        return $this->redirectAfterRole($request->user());
    }

    private function redirectAfterRole(User $user): RedirectResponse
    {
        $this->activeOferta->sync($user);
        $this->activePeriodo->sync($user);

        if ($this->activeOferta->needsSelection($user)) {
            return redirect()->route('oferta.select');
        }

        if ($this->activePeriodo->missing($user)) {
            return redirect()
                ->route('oferta.select')
                ->withErrors(['periodo' => 'No existe ningún periodo activo.']);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
