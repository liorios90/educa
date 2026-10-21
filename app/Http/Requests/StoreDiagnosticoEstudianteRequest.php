<?php

namespace App\Http\Requests;

use App\Auth\ActiveDocenteContexto;
use App\Auth\DocenteContexto;
use App\Enums\Role;
use App\Models\Alumno;
use App\Services\AsignacionesDocente;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiagnosticoEstudianteRequest extends FormRequest
{
    private ?DocenteContexto $contexto = null;

    private ?Alumno $alumno = null;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasRole(Role::Docente);
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'alumno_id' => ['required', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alumno_id.required' => 'Selecciona un estudiante.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $user = $this->user();

                if ($user === null || $user->establecimiento_id === null) {
                    abort(404);
                }

                $contexto = $this->contextoActivo()->get($user);

                if ($contexto === null) {
                    $validator->errors()->add('alumno_id', 'Selecciona un aula y una materia para analizar al estudiante.');

                    return;
                }

                $alumno = Alumno::query()
                    ->with('persona')
                    ->find((int) $this->input('alumno_id'));

                if ($alumno === null || (int) $alumno->persona?->establecimiento_id !== (int) $user->establecimiento_id) {
                    abort(404);
                }

                $enElAula = $this->asignaciones()
                    ->alumnos($contexto)
                    ->contains(fn (Alumno $inscrito): bool => $inscrito->id === $alumno->id);

                if (! $enElAula) {
                    abort(404);
                }

                $this->contexto = $contexto;
                $this->alumno = $alumno;
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

    public function alumno(): Alumno
    {
        if (! $this->alumno instanceof Alumno) {
            abort(404);
        }

        return $this->alumno;
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
