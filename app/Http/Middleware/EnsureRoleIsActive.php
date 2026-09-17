<?php

namespace App\Http\Middleware;

use App\Auth\ActiveRole;
use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleIsActive
{
    public function __construct(private ActiveRole $activeRole) {}

    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        $required = Role::tryFrom($role);

        abort_unless($user !== null && $required !== null && $user->hasRole($required), 403);

        $active = $this->activeRole->get($user);

        if ($active !== null) {
            abort_unless($active === $required, 403);
        }

        return $next($request);
    }
}
