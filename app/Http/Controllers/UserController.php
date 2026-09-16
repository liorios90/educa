<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users', [
            'users' => User::query()->with('roles')->orderBy('name')->get(),
            'usersIndexRoute' => $this->usersIndexRoute(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => Role::cases(),
            'usersIndexRoute' => $this->usersIndexRoute(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->safe()->only(['name', 'email', 'password']));
        $user->assignRole($request->enum('role', Role::class));

        return redirect()
            ->route($this->usersIndexRoute($request))
            ->with('status', 'user-created');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::cases(),
            'usersIndexRoute' => $this->usersIndexRoute(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->only(['name', 'email']));

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
        $user->syncRoles([$request->enum('role', Role::class)]);

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

    private function usersIndexRoute(?Request $request = null): string
    {
        $request ??= request();

        return $request->routeIs('sistemas.*') ? 'sistemas.users' : 'admin.users';
    }
}
