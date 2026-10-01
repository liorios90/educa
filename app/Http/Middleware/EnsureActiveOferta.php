<?php

namespace App\Http\Middleware;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveOferta
{
    public function __construct(
        private ActiveOferta $activeOferta,
        private ActivePeriodo $activePeriodo,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $this->shouldSkip($request)) {
            return $next($request);
        }

        $this->activeOferta->sync($user);
        $this->activePeriodo->sync($user);

        if ($this->activeOferta->needsSelection($user) && $request->routeIs('dashboard')) {
            return redirect()->guest(route('oferta.select'));
        }

        if ($this->activePeriodo->missing($user) && $request->routeIs('dashboard')) {
            return redirect()
                ->guest(route('oferta.select'))
                ->withErrors(['periodo' => 'No existe ningún periodo activo.']);
        }

        return $next($request);
    }

    private function shouldSkip(Request $request): bool
    {
        return $request->routeIs([
            'logout',
            'role.select',
            'role.store',
            'oferta.select',
            'oferta.store',
            'verification.*',
            'password.*',
        ]);
    }
}
