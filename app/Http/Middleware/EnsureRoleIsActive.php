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

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $required = collect($roles)
            ->flatMap(fn (string $role): array => explode('|', $role))
            ->map(fn (string $role): ?Role => Role::tryFrom(trim($role)))
            ->filter()
            ->values();

        abort_unless(
            $user !== null
            && $required->isNotEmpty()
            && $required->contains(fn (Role $role): bool => $user->hasRole($role)),
            403,
        );

        $active = $this->activeRole->get($user);

        if ($active !== null) {
            abort_unless($required->contains($active), 403);
        }

        return $next($request);
    }
}
