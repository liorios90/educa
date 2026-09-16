<?php

namespace App\View\Components;

use App\Models\Establecimiento;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class EstablecimientoInicio extends Component
{
    public function render(): View
    {
        $user = Auth::user();
        $establecimiento = null;

        if ($user instanceof User) {
            $user->loadMissing('establecimiento');
            $establecimiento = $user->establecimiento;
        }

        return view('components.establecimiento-inicio', [
            'establecimiento' => $establecimiento instanceof Establecimiento ? $establecimiento : null,
            'logoUrl' => $establecimiento instanceof Establecimiento ? $establecimiento->logoUrl() : null,
        ]);
    }
}
