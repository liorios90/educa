<?php

namespace App\Auth;

use App\Enums\ModoAsistencia;
use App\Models\User;
use App\Services\AsignacionesDocente;

class ActiveAsistenciaContexto
{
    public const MODO_KEY = 'active_asistencia_modo';

    public const AULA_KEY = 'active_asistencia_aula';

    public const ASIGNATURA_KEY = 'active_asistencia_asignatura';

    public const CICLO_KEY = 'active_asistencia_ciclo';

    public const PARCIAL_KEY = 'active_asistencia_parcial';

    public function __construct(
        private AsignacionesDocente $asignaciones,
        private ActiveDocenteContexto $docenteContexto,
    ) {}

    public function get(?User $user = null): ?ContextoAsistencia
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        $modo = ModoAsistencia::tryFrom((string) session(self::MODO_KEY));
        $aulaId = session(self::AULA_KEY);

        if ($modo === null || ! is_numeric($aulaId)) {
            return null;
        }

        $contexto = $this->asignaciones->contextoAsistencia(
            $user,
            (int) $aulaId,
            $modo,
            $this->entero(session(self::ASIGNATURA_KEY)),
            $this->entero(session(self::CICLO_KEY)),
            $this->entero(session(self::PARCIAL_KEY)),
        );

        if ($contexto === null) {
            $this->clear();
        }

        return $contexto;
    }

    public function set(ContextoAsistencia $contexto): void
    {
        session([
            self::MODO_KEY => $contexto->modo->value,
            self::AULA_KEY => $contexto->aula->id,
            self::ASIGNATURA_KEY => $contexto->asignatura?->id,
            self::CICLO_KEY => $contexto->ciclo?->id,
            self::PARCIAL_KEY => $contexto->parcialNumero,
        ]);

        if (
            $contexto->modo === ModoAsistencia::Asignatura
            && $contexto->asignatura !== null
            && $contexto->ciclo !== null
            && $contexto->parcialNumero !== null
        ) {
            $this->docenteContexto->set(new DocenteContexto(
                $contexto->aula,
                $contexto->asignatura,
                $contexto->ciclo,
                $contexto->parcialNumero,
            ));
        }
    }

    public function clear(): void
    {
        session()->forget([
            self::MODO_KEY,
            self::AULA_KEY,
            self::ASIGNATURA_KEY,
            self::CICLO_KEY,
            self::PARCIAL_KEY,
        ]);
    }

    private function entero(mixed $valor): ?int
    {
        return is_numeric($valor) ? (int) $valor : null;
    }
}
