<?php

namespace App\Http\Requests;

use App\Auth\DocenteContexto;
use App\Enums\Role;
use App\Enums\TipoCierreEvaluacion;
use App\Services\AsignacionesDocente;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SelectDocenteCierreContextoRequest extends FormRequest
{
    private ?DocenteContexto $contexto = null;

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
            'establecimiento_aula_id' => ['required', 'integer'],
            'establecimiento_asignatura_id' => ['required', 'integer'],
            'establecimiento_periodo_ciclo_id' => $this->tipo()->requiereCiclo()
                ? ['required', 'integer']
                : ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'establecimiento_aula_id.required' => 'Selecciona un aula.',
            'establecimiento_asignatura_id.required' => 'Selecciona una materia.',
            'establecimiento_periodo_ciclo_id.required' => 'Selecciona un quimestre o trimestre.',
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

                if ($user === null) {
                    abort(404);
                }

                $contexto = $this->asignaciones()->contextoCierre(
                    $user,
                    (int) $this->input('establecimiento_aula_id'),
                    (int) $this->input('establecimiento_asignatura_id'),
                    $this->tipo(),
                    $this->filled('establecimiento_periodo_ciclo_id')
                        ? (int) $this->input('establecimiento_periodo_ciclo_id')
                        : null,
                );

                if ($contexto === null) {
                    abort(404);
                }

                $this->contexto = $contexto;
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

    public function tipo(): TipoCierreEvaluacion
    {
        return TipoCierreEvaluacion::fromRuta((string) $this->route('tipo'));
    }

    private function asignaciones(): AsignacionesDocente
    {
        return $this->container->make(AsignacionesDocente::class);
    }
}
