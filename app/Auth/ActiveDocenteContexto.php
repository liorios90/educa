<?php

namespace App\Auth;

use App\Enums\Role;
use App\Models\User;
use App\Services\AsignacionesDocente;

class ActiveDocenteContexto
{
    public const AULA_KEY = 'active_docente_aula';

    public const ASIGNATURA_KEY = 'active_docente_asignatura';

    public const CICLO_KEY = 'active_docente_ciclo';

    public const PARCIAL_KEY = 'active_docente_parcial';

    public function __construct(
        private ActiveRole $activeRole,
        private AsignacionesDocente $asignaciones,
    ) {}

    public function appliesTo(?User $user): bool
    {
        if (! $user instanceof User || ! $user->hasRole(Role::Docente)) {
            return false;
        }

        $active = $this->activeRole->get($user);

        return $active === null || $active === Role::Docente;
    }

    public function get(?User $user = null): ?DocenteContexto
    {
        $user ??= auth()->user();

        if (! $user instanceof User || ! $this->appliesTo($user)) {
            return null;
        }

        $aulaId = session(self::AULA_KEY);
        $asignaturaId = session(self::ASIGNATURA_KEY);
        $cicloId = session(self::CICLO_KEY);
        $parcialId = session(self::PARCIAL_KEY);

        if (! is_numeric($aulaId) || ! is_numeric($asignaturaId) || ! is_numeric($cicloId) || ! is_numeric($parcialId)) {
            return null;
        }

        $contexto = $this->asignaciones->contexto(
            $user,
            (int) $aulaId,
            (int) $asignaturaId,
            (int) $cicloId,
            (int) $parcialId,
        );

        if ($contexto === null) {
            $this->clear();
        }

        return $contexto;
    }

    public function set(DocenteContexto $contexto): void
    {
        session([
            self::AULA_KEY => $contexto->aula->id,
            self::ASIGNATURA_KEY => $contexto->asignatura->id,
            self::CICLO_KEY => $contexto->ciclo->id,
            self::PARCIAL_KEY => $contexto->parcialNumero,
        ]);
    }

    public function clear(): void
    {
        session()->forget([
            self::AULA_KEY,
            self::ASIGNATURA_KEY,
            self::CICLO_KEY,
            self::PARCIAL_KEY,
        ]);
    }
}
