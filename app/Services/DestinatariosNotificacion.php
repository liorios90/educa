<?php

namespace App\Services;

use App\Auth\ActiveRole;
use App\Enums\Role;
use App\Models\Alumno;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoMatricula;
use App\Models\Padre;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class DestinatariosNotificacion
{
    public function __construct(
        private ActiveRole $activeRole,
        private AsignacionesDocente $asignaciones,
        private LibretaFamiliar $familiar,
    ) {}

    /**
     * @return Collection<int, array{id: int, nombre: string, rol: string}>
     */
    public function para(User $user): Collection
    {
        $destinatarios = $this->usuariosPermitidos($user);
        $this->cargarHijosDePadres($destinatarios);

        return $destinatarios
            ->map(fn (User $destinatario): array => [
                'id' => (int) $destinatario->id,
                'nombre' => $destinatario->nombreVisible(),
                'rol' => $this->etiquetaDestinatario($destinatario, $user),
            ])
            ->sortBy([
                ['nombre', 'asc'],
                ['id', 'asc'],
            ], SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @return list<int>
     */
    public function idsPermitidos(User $user): array
    {
        return $this->usuariosPermitidos($user)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, User>
     */
    private function usuariosPermitidos(User $user): Collection
    {
        $rol = $this->rolDe($user);

        $usuarios = match ($rol) {
            Role::Alumno => $this->docentesDeAlumnos($this->familiar->alumnosDe($user), $user),
            Role::Padre => $this->docentesDeAlumnos($this->familiar->alumnosDe($user), $user),
            Role::Docente => $this->alumnosPadresYColegas($user),
            Role::Admin, Role::Secretaria => $this->docentesYPadres($user),
            default => collect(),
        };

        return $usuarios
            ->unique('id')
            ->reject(fn (User $destinatario): bool => (int) $destinatario->id === (int) $user->id)
            ->values();
    }

    /**
     * @param  Collection<int, Alumno>  $alumnos
     * @return Collection<int, User>
     */
    private function docentesDeAlumnos(Collection $alumnos, User $actor): Collection
    {
        $userIds = collect();

        foreach ($alumnos as $alumno) {
            $matriculas = EstablecimientoMatricula::query()
                ->with('aula')
                ->where('alumno_id', $alumno->id)
                ->where('establecimiento_id', $actor->establecimiento_id)
                ->whereHas('periodo', fn ($query) => $query->activo())
                ->get();

            foreach ($matriculas as $matricula) {
                $aula = $matricula->aula;

                if ($aula === null) {
                    continue;
                }

                $asignaciones = EstablecimientoAulaDocente::query()
                    ->with(['malla', 'empleado.persona'])
                    ->where('establecimiento_aula_id', $aula->id)
                    ->get();

                foreach ($asignaciones as $asignacion) {
                    $malla = $asignacion->malla;

                    if ($malla === null) {
                        continue;
                    }

                    $enClase = $this->asignaciones->alumnosDe($aula, $malla)
                        ->contains(fn (Alumno $candidato): bool => (int) $candidato->id === (int) $alumno->id);

                    if (! $enClase) {
                        continue;
                    }

                    $userId = $asignacion->empleado?->persona?->user_id;

                    if ($userId !== null) {
                        $userIds->push((int) $userId);
                    }
                }
            }
        }

        return $this->usuariosPorIds($userIds);
    }

    /**
     * @return Collection<int, User>
     */
    private function alumnosPadresYColegas(User $docente): Collection
    {
        $alumnos = $this->alumnosDelDocente($docente);
        $userIds = collect();

        foreach ($alumnos as $alumno) {
            $alumno->loadMissing(['persona', 'padre.persona']);

            if ($alumno->persona?->user_id !== null) {
                $userIds->push((int) $alumno->persona->user_id);
            }

            if ($alumno->padre?->persona?->user_id !== null) {
                $userIds->push((int) $alumno->padre->persona->user_id);
            }
        }

        return $this->usuariosPorIds($userIds)
            ->concat($this->usuariosConRoles($docente, [Role::Docente]))
            ->values();
    }

    /**
     * @return Collection<int, Alumno>
     */
    private function alumnosDelDocente(User $docente): Collection
    {
        $ids = collect();

        foreach ($this->asignaciones->forUser($docente) as $asignacion) {
            $aula = $asignacion->aula;
            $malla = $asignacion->malla;

            if ($aula === null || $malla === null) {
                continue;
            }

            $ids = $ids->merge($this->asignaciones->alumnosDe($aula, $malla)->pluck('id'));
        }

        $ids = $ids->map(fn (mixed $id): int => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Alumno::query()
            ->with(['persona', 'padre.persona'])
            ->whereIn('id', $ids->all())
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function docentesYPadres(User $user): Collection
    {
        return $this->usuariosConRoles($user, [Role::Docente, Role::Padre]);
    }

    /**
     * @param  list<Role>  $roles
     * @return Collection<int, User>
     */
    private function usuariosConRoles(User $user, array $roles): Collection
    {
        if ($user->establecimiento_id === null) {
            return collect();
        }

        return User::query()
            ->with(['persona', 'roles'])
            ->where('establecimiento_id', $user->establecimiento_id)
            ->where('id', '!=', $user->id)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', array_map(
                fn (Role $rol): string => $rol->value,
                $roles,
            )))
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, int>  $ids
     * @return Collection<int, User>
     */
    private function usuariosPorIds(Collection $ids): Collection
    {
        $unicos = $ids->unique()->values();

        if ($unicos->isEmpty()) {
            return collect();
        }

        return User::query()
            ->with(['persona', 'roles'])
            ->whereIn('id', $unicos->all())
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    private function rolDe(User $user): ?Role
    {
        return $this->activeRole->get($user)
            ?? Role::tryFrom((string) $user->getRoleNames()->first());
    }

    /**
     * @param  Collection<int, User>  $destinatarios
     */
    private function cargarHijosDePadres(Collection $destinatarios): void
    {
        if ($destinatarios->isEmpty()) {
            return;
        }

        (new EloquentCollection($destinatarios->all()))
            ->load(['persona.padres.alumnos.persona']);
    }

    private function etiquetaDestinatario(User $destinatario, User $actor): string
    {
        $hijos = $this->nombresHijosDe($destinatario, $actor);
        $partes = [];

        foreach ([Role::Docente, Role::Padre, Role::Alumno, Role::Admin, Role::Secretaria, Role::Sistemas, Role::Empleado] as $rol) {
            if (! $destinatario->hasRole($rol)) {
                continue;
            }

            if ($rol === Role::Padre && $hijos !== []) {
                $partes[] = 'Padre de '.implode(', ', $hijos);

                continue;
            }

            $partes[] = $rol->label();
        }

        return $partes !== [] ? implode(', ', $partes) : 'Usuario';
    }

    /**
     * @return list<string>
     */
    private function nombresHijosDe(User $destinatario, User $actor): array
    {
        if (! $destinatario->hasRole(Role::Padre)) {
            return [];
        }

        $nombres = $destinatario->persona?->padres
            ?->flatMap(fn (Padre $padre): Collection => $padre->alumnos)
            ?? collect();

        return $nombres
            ->filter(function (Alumno $alumno) use ($actor): bool {
                if ($actor->establecimiento_id === null) {
                    return true;
                }

                return (int) $alumno->persona?->establecimiento_id === (int) $actor->establecimiento_id;
            })
            ->map(function (Alumno $alumno): string {
                return trim(($alumno->persona?->apellidos ?? '').' '.($alumno->persona?->nombres ?? ''));
            })
            ->filter(fn (string $nombre): bool => $nombre !== '')
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
