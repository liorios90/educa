<?php

namespace App\Http\Controllers\Auth;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Auth\ActiveRole;
use App\Auth\AuthContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, ActiveRole $activeRole, ActiveOferta $activeOferta, ActivePeriodo $activePeriodo, AuthContext $authContext): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $activeRole->clear();
        $activeOferta->clear();
        $activePeriodo->clear();

        $user = $request->user();

        if ($user instanceof User) {
            $authContext->remember($user);
        }

        $roles = $user !== null ? $activeRole->assigned($user) : collect();

        if ($roles->count() > 1) {
            return redirect()->route('role.select');
        }

        if ($roles->count() === 1) {
            $activeRole->set($roles->first());
        }

        if ($user instanceof User) {
            $activeOferta->sync($user);
            $activePeriodo->sync($user);

            if ($activeOferta->needsSelection($user)) {
                return redirect()->route('oferta.select');
            }

            if ($activePeriodo->missing($user)) {
                return redirect()
                    ->route('oferta.select')
                    ->withErrors(['periodo' => 'No existe ningún periodo activo.']);
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
