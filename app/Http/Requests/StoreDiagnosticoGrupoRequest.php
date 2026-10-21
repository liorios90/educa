<?php

namespace App\Http\Requests;

use App\Auth\ActiveDocenteContexto;
use App\Auth\DocenteContexto;
use App\Enums\Role;
use App\Models\Alumno;
use App\Services\AsignacionesDocente;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class StoreDiagnosticoGrupoRequest extends FormRequest
{
    private ?DocenteContexto $contexto = null;

    /** @var Collection<int, Alumno>|null */
    private ?Collection $alumnos = null;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasRole(Role::Docente);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $user = $this->user();

                if ($user === null || $user->establecimiento_id === null) {
                    abort(404);
                }

                $contexto = $this->contextoActivo()->get($user);

                if ($contexto === null) {
                    $validator->errors()->add('grupo', 'Selecciona un aula y una materia para analizar al paralelo.');

                    return;
                }

                $alumnos = $this->asignaciones()->alumnos($contexto);

                if ($alumnos->isEmpty()) {
                    $validator->errors()->add('grupo', 'No hay alumnos matriculados en este aula o asignatura.');

                    return;
                }

                $this->contexto = $contexto;
                $this->alumnos = $alumnos;
            },
        ];
    }

    public function contexto(): DocenteContexto
    {
        if (! $this->contexto instanceof DocenteContexto) {
            abort(404);
        }

        return $this->contexto;
    }

    /**
     * @return Collection<int, Alumno>
     */
    public function alumnos(): Collection
    {
        if (! $this->alumnos instanceof Collection) {
            abort(404);
        }

        return $this->alumnos;
    }

    private function contextoActivo(): ActiveDocenteContexto
    {
        return $this->container->make(ActiveDocenteContexto::class);
    }

    private function asignaciones(): AsignacionesDocente
    {
        return $this->container->make(AsignacionesDocente::class);
    }
}
