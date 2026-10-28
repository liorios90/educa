<?php

namespace App\Http\Requests;

use App\Auth\ContextoComportamiento;
use App\Enums\Role;
use App\Services\AsignacionesDocente;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreComportamientoContextoRequest extends FormRequest
{
    private ?ContextoComportamiento $contexto = null;

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
            'establecimiento_periodo_ciclo_id' => ['required', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'establecimiento_aula_id.required' => 'Selecciona un aula.',
            'establecimiento_asignatura_id.required' => 'Selecciona una asignatura.',
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

                $contexto = $this->asignaciones()->contextoComportamiento(
                    $user,
                    (int) $this->input('establecimiento_aula_id'),
                    (int) $this->input('establecimiento_asignatura_id'),
                    (int) $this->input('establecimiento_periodo_ciclo_id'),
                );

                if ($contexto === null) {
                    abort(404);
                }

                $this->contexto = $contexto;
            },
        ];
    }

    public function contexto(): ContextoComportamiento
    {
        if (! $this->contexto instanceof ContextoComportamiento) {
            abort(404);
        }

        return $this->contexto;
    }

    private function asignaciones(): AsignacionesDocente
    {
        return $this->container->make(AsignacionesDocente::class);
    }
}
