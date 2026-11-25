<?php

namespace App\Services;

use App\Auth\ActiveRole;
use App\Enums\Role;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\EstablecimientoAula;
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
     * @return Collection<int, array{id: int, nombre: string, rol: string, roles: list<string>, aulas: list<int>}>
     */
    public function para(User $user): Collection
    {
        $fichas = $this->fichasPermitidas($user);
        $this->cargarDatosDeEtiqueta($fichas->map(fn (array $ficha): User => $ficha['user']));

        return $fichas
            ->map(fn (array $ficha): array => [
                'id' => (int) $ficha['user']->id,
                'nombre' => $ficha['user']->nombreVisible(),
                'rol' => $this->etiquetaDestinatario($ficha['user'], $user),
                'roles' => $this->clavesDeRol($ficha['user']),
                'aulas' => $ficha['aulas'],
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
        return $this->fichasPermitidas($user)
            ->map(fn (array $ficha): int => (int) $ficha['user']->id)
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{id: int, etiqueta: string}>
     */
    public function aulasPara(User $user): Collection
    {
        if ($this->rolDe($user) !== Role::Docente) {
            return collect();
        }

        return $this->asignaciones->forUser($user)
            ->map(fn (EstablecimientoAulaDocente $asignacion): ?EstablecimientoAula => $asignacion->aula)
            ->filter()
            ->unique('id')
            ->map(fn (EstablecimientoAula $aula): array => [
                'id' => (int) $aula->id,
                'etiqueta' => $aula->etiqueta(),
            ])
            ->sortBy('etiqueta', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @return Collection<int, array{user: User, aulas: list<int>}>
     */
    private function fichasPermitidas(User $user): Collection
    {
        $rol = $this->rolDe($user);

        $fichas = match ($rol) {
            Role::Alumno => $this->fichasSinAula($this->docentesDeAlumnos($this->familiar->alumnosDe($user), $user)),
            Role::Padre => $this->fichasSinAula($this->docentesDeAlumnos($this->familiar->alumnosDe($user), $user)),
            Role::Docente => $this->fichasDocente($user),
            Role::Admin, Role::Secretaria => $this->fichasSinAula($this->docentesYPadres($user)),
            default => collect(),
        };

        return $fichas
            ->unique(fn (array $ficha): int => (int) $ficha['user']->id)
            ->reject(fn (array $ficha): bool => (int) $ficha['user']->id === (int) $user->id)
            ->values();
    }

    /**
     * @param  Collection<int, User>  $usuarios
     * @return Collection<int, array{user: User, aulas: list<int>}>
     */
    private function fichasSinAula(Collection $usuarios): Collection
    {
        return $usuarios
            ->map(fn (User $usuario): array => [
                'user' => $usuario,
                'aulas' => [],
            ])
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
     * @return Collection<int, array{user: User, aulas: list<int>}>
     */
    private function fichasDocente(User $docente): Collection
    {
        /** @var list<array{alumno: Alumno, aula: int}> $filas */
        $filas = [];

        foreach ($this->asignaciones->forUser($docente) as $asignacion) {
            $aula = $asignacion->aula;
            $malla = $asignacion->malla;

            if ($aula === null || $malla === null) {
                continue;
            }

            foreach ($this->asignaciones->alumnosDe($aula, $malla) as $alumno) {
                $filas[] = [
                    'alumno' => $alumno,
                    'aula' => (int) $aula->id,
                ];
            }
        }

        (new EloquentCollection(array_map(
            fn (array $fila): Alumno => $fila['alumno'],
            $filas,
        )))->load(['persona', 'padre.persona']);

        /** @var array<int, list<int>> $aulasPorUsuario */
        $aulasPorUsuario = [];

        foreach ($filas as $fila) {
            foreach ([
                $fila['alumno']->persona?->user_id,
                $fila['alumno']->padre?->persona?->user_id,
            ] as $userId) {
                if ($userId === null) {
                    continue;
                }

                $aulasPorUsuario[(int) $userId] ??= [];
                $aulasPorUsuario[(int) $userId][] = $fila['aula'];
            }
        }

        return $this->usuariosPorIds(collect(array_keys($aulasPorUsuario)))
            ->concat($this->usuariosConRoles($docente, [Role::Admin, Role::Secretaria, Role::Empleado]))
            ->unique('id')
            ->map(function (User $usuario) use ($aulasPorUsuario): array {
                $aulas = array_values(array_unique($aulasPorUsuario[(int) $usuario->id] ?? []));
                sort($aulas);

                return [
                    'user' => $usuario,
                    'aulas' => $aulas,
                ];
            })
            ->values();
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
    private function cargarDatosDeEtiqueta(Collection $destinatarios): void
    {
        if ($destinatarios->isEmpty()) {
            return;
        }

        (new EloquentCollection($destinatarios->all()))
            ->load([
                'persona.padres.alumnos.persona',
                'persona.empleados.asignaciones.aula.establecimientoGrado.grado',
                'persona.empleados.asignaciones.aula.periodo',
            ]);
    }

    /**
     * @return list<string>
     */
    private function clavesDeRol(User $destinatario): array
    {
        $claves = [];

        foreach ([Role::Docente, Role::Padre, Role::Alumno, Role::Admin, Role::Secretaria, Role::Sistemas, Role::Empleado] as $rol) {
            if ($destinatario->hasRole($rol)) {
                $claves[] = $rol->value;
            }
        }

        return $claves;
    }

    private function etiquetaDestinatario(User $destinatario, User $actor): string
    {
        $hijos = $this->nombresHijosDe($destinatario, $actor);
        $aulas = $this->aulasDe($destinatario, $actor);
        $partes = [];

        foreach ([Role::Docente, Role::Padre, Role::Alumno, Role::Admin, Role::Secretaria, Role::Sistemas, Role::Empleado] as $rol) {
            if (! $destinatario->hasRole($rol)) {
                continue;
            }

            if ($rol === Role::Docente && $aulas !== []) {
                $partes[] = 'Docente de '.implode(', ', $aulas);

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
    private function aulasDe(User $destinatario, User $actor): array
    {
        if (! $destinatario->hasRole(Role::Docente)) {
            return [];
        }

        $asignaciones = $destinatario->persona?->empleados
            ?->flatMap(fn (Empleado $empleado): Collection => $empleado->asignaciones)
            ?? collect();

        return $asignaciones
            ->map(fn (EstablecimientoAulaDocente $asignacion): ?EstablecimientoAula => $asignacion->aula)
            ->filter()
            ->filter(function (EstablecimientoAula $aula) use ($actor): bool {
                if ($actor->establecimiento_id !== null && (int) $aula->establecimiento_id !== (int) $actor->establecimiento_id) {
                    return false;
                }

                return $aula->periodo?->activo === true;
            })
            ->unique('id')
            ->map(fn (EstablecimientoAula $aula): string => $aula->etiqueta())
            ->filter(fn (string $etiqueta): bool => $etiqueta !== '')
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
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
