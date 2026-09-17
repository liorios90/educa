<?php

namespace App\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class ActiveRole
{
    public const SESSION_KEY = 'active_role';

    public function get(?User $user = null): ?Role
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        $role = Role::tryFrom((string) session(self::SESSION_KEY));

        if ($role === null || ! $user->hasRole($role)) {
            return null;
        }

        return $role;
    }

    public function set(Role $role): void
    {
        session([self::SESSION_KEY => $role->value]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * @return Collection<int, Role>
     */
    public function assigned(User $user): Collection
    {
        return $user->getRoleNames()
            ->map(fn (string $name): ?Role => Role::tryFrom($name))
            ->filter()
            ->values();
    }
}
