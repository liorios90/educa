<?php

namespace App\Auth;

use App\Models\User;
use App\Services\AsignacionesDocente;

class ActiveDestrezaContexto
{
    public const AULA_KEY = 'active_destreza_aula';

    public const ASIGNATURA_KEY = 'active_destreza_asignatura';

    public const CICLO_KEY = 'active_destreza_ciclo';

    public function __construct(private AsignacionesDocente $asignaciones) {}

    public function get(?User $user = null): ?ContextoDestreza
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        $aulaId = session(self::AULA_KEY);
        $asignaturaId = session(self::ASIGNATURA_KEY);
        $cicloId = session(self::CICLO_KEY);

        if (! is_numeric($aulaId) || ! is_numeric($asignaturaId) || ! is_numeric($cicloId)) {
            return null;
        }

        $contexto = $this->asignaciones->contextoDestreza($user, (int) $aulaId, (int) $asignaturaId, (int) $cicloId);

        if ($contexto === null) {
            $this->clear();
        }

        return $contexto;
    }

    public function set(ContextoDestreza $contexto): void
    {
        session([
            self::AULA_KEY => $contexto->aula->id,
            self::ASIGNATURA_KEY => $contexto->asignatura->id,
            self::CICLO_KEY => $contexto->ciclo->id,
        ]);
    }

    public function clear(): void
    {
        session()->forget([
            self::AULA_KEY,
            self::ASIGNATURA_KEY,
            self::CICLO_KEY,
        ]);
    }
}
