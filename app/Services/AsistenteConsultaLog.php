<?php

namespace App\Services;

use App\Models\AsistenteConsulta;
use App\Models\Establecimiento;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AsistenteConsultaLog
{
    public function registrar(Establecimiento $establecimiento, string $pregunta, ?User $usuario = null): void
    {
        $usuario ??= Auth::user();

        try {
            AsistenteConsulta::query()->create([
                'establecimiento_id' => $establecimiento->id,
                'user_id' => $usuario?->id,
                'usuario' => $usuario?->name ?: 'Sistema',
                'pregunta' => $pregunta,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
