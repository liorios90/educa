<?php

namespace App\Http\Middleware;

use App\Auth\ActiveRole;
use App\Auth\AuthContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveRole
{
    public function __construct(
        private ActiveRole $activeRole,
        private AuthContext $authContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $request->routeIs('logout')) {
            return $next($request);
        }

        $this->authContext->rememberIfMissing($user);

        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $assigned = $this->activeRole->assigned($user);

        if ($assigned->count() <= 1) {
            if ($assigned->count() === 1) {
                $this->activeRole->set($assigned->first());
            }

            return $next($request);
        }

        if ($this->activeRole->get($user) !== null) {
            return $next($request);
        }

        return redirect()->guest(route('role.select'));
    }

    private function shouldSkip(Request $request): bool
    {
        return $request->routeIs([
            'logout',
            'role.select',
            'role.store',
            'verification.*',
            'password.*',
        ]);
    }
}
