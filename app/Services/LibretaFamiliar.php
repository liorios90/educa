<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Alumno;
use App\Models\EstablecimientoMatricula;
use App\Models\Padre;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LibretaFamiliar
{
    /**
     * @return Collection<int, Alumno>
     */
    public function alumnosDe(User $user): Collection
    {
        $user->loadMissing('persona');
        $persona = $user->persona;

        if ($persona === null) {
            return collect();
        }

        if ($user->hasRole(Role::Alumno)) {
            return Alumno::query()
                ->with('persona')
                ->where('persona_id', $persona->id)
                ->orderBy('id')
                ->get();
        }

        if (! $user->hasRole(Role::Padre)) {
            return collect();
        }

        $padre = Padre::query()->where('persona_id', $persona->id)->first();

        if ($padre === null) {
            return collect();
        }

        return $padre->alumnos()
            ->with('persona')
            ->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $user->establecimiento_id))
            ->join('personas', 'personas.id', '=', 'alumnos.persona_id')
            ->orderBy('personas.apellidos')
            ->orderBy('personas.nombres')
            ->orderBy('alumnos.id')
            ->select('alumnos.*')
            ->get();
    }

    /**
     * @param  Collection<int, Alumno>  $alumnos
     */
    public function seleccionar(Request $request, Collection $alumnos): ?Alumno
    {
        if ($alumnos->isEmpty()) {
            return null;
        }

        $solicitado = $request->filled('alumno_id')
            ? $alumnos->first(fn (Alumno $alumno): bool => (int) $alumno->id === $request->integer('alumno_id'))
            : null;

        abort_if($request->filled('alumno_id') && $solicitado === null, 404);

        if ($request->user()?->hasRole(Role::Alumno)) {
            return $solicitado ?? $alumnos->first();
        }

        if ($alumnos->count() === 1) {
            return $alumnos->first();
        }

        return $solicitado;
    }

    public function matriculaActiva(Alumno $alumno, User $user): ?EstablecimientoMatricula
    {
        return EstablecimientoMatricula::query()
            ->with(['aula.periodo', 'aula.establecimientoGrado.grado', 'periodo'])
            ->where('alumno_id', $alumno->id)
            ->where('establecimiento_id', $user->establecimiento_id)
            ->whereHas('periodo', fn ($query) => $query->activo())
            ->orderByDesc('id')
            ->first();
    }
}
