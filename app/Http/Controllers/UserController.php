<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Establecimiento;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users', [
            'users' => User::query()->with(['roles', 'establecimiento'])->orderBy('name')->get(),
            'usersIndexRoute' => $this->usersIndexRoute(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', $this->formData());
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $role = $request->enum('role', Role::class);
        $user = User::create([
            ...$request->safe()->only(['name', 'email', 'password']),
            'establecimiento_id' => $this->establecimientoIdFor($role, $request->validated('establecimiento_id')),
        ]);
        $user->assignRole($role);

        return redirect()
            ->route($this->usersIndexRoute($request))
            ->with('status', 'user-created');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $role = $request->enum('role', Role::class);
        $user->fill([
            ...$request->safe()->only(['name', 'email']),
            'establecimiento_id' => $this->establecimientoIdFor($role, $request->validated('establecimiento_id')),
        ]);

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
        $user->syncRoles([$role]);

        return redirect()
            ->route($this->usersIndexRoute($request))
            ->with('status', 'user-updated');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            return back()->with('status', 'user-self-delete-blocked');
        }

        $user->delete();

        return redirect()
            ->route($this->usersIndexRoute($request))
            ->with('status', 'user-deleted');
    }

    /**
     * @return array{roles: list<Role>, establecimientos: Collection<int, Establecimiento>, usersIndexRoute: string, rolesWithEstablecimiento: list<string>}
     */
    private function formData(): array
    {
        return [
            'roles' => Role::cases(),
            'establecimientos' => Establecimiento::query()->orderBy('nombre')->get(['id', 'nombre']),
            'usersIndexRoute' => $this->usersIndexRoute(),
            'rolesWithEstablecimiento' => array_values(array_map(
                fn (Role $role): string => $role->value,
                array_filter(Role::cases(), fn (Role $role): bool => $role->requiresEstablecimiento()),
            )),
        ];
    }

    private function establecimientoIdFor(Role $role, mixed $establecimientoId): ?int
    {
        if (! $role->requiresEstablecimiento()) {
            return null;
        }

        return is_numeric($establecimientoId) ? (int) $establecimientoId : null;
    }

    private function usersIndexRoute(?Request $request = null): string
    {
        $request ??= request();

        return $request->routeIs('sistemas.*') ? 'sistemas.users' : 'admin.users';
    }
}
