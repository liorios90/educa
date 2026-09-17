<?php

namespace App\View\Components;

use App\Auth\ActiveRole;
use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Component;
use Illuminate\View\View;

class EstablecimientoInicio extends Component
{
    public const SISTEMAS_LOGO_PATH = 'establecimientos/logos/dm2.jpeg';

    public function __construct(private ActiveRole $activeRole) {}

    public static function sistemasLogoUrl(): ?string
    {
        if (! Storage::disk('public')->exists(self::SISTEMAS_LOGO_PATH)) {
            return null;
        }

        return Storage::disk('public')->url(self::SISTEMAS_LOGO_PATH);
    }

    public function render(): View
    {
        $user = Auth::user();
        $activeRole = $user instanceof User ? $this->activeRole->get($user) : null;

        if ($activeRole === Role::Sistemas) {
            return view('components.establecimiento-inicio', [
                'establecimiento' => null,
                'isSistemas' => true,
                'logoUrl' => self::sistemasLogoUrl(),
            ]);
        }

        $establecimiento = null;

        if ($user instanceof User && $activeRole?->requiresEstablecimiento()) {
            $user->loadMissing('establecimiento');
            $establecimiento = $user->establecimiento;
        }

        return view('components.establecimiento-inicio', [
            'establecimiento' => $establecimiento instanceof Establecimiento ? $establecimiento : null,
            'isSistemas' => false,
            'logoUrl' => $establecimiento instanceof Establecimiento ? $establecimiento->logoUrl() : null,
        ]);
    }
}
