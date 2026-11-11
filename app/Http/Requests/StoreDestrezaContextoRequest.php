<?php

namespace App\Http\Requests;

use App\Auth\ContextoDestreza;
use App\Enums\Role;
use App\Services\AsignacionesDocente;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreDestrezaContextoRequest extends FormRequest
{
    private ?ContextoDestreza $contexto = null;

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

                $asignaciones = $this->container->make(AsignacionesDocente::class);
                $base = $asignaciones->contexto(
                    $user,
                    (int) $this->input('establecimiento_aula_id'),
                    (int) $this->input('establecimiento_asignatura_id'),
                    (int) $this->input('establecimiento_periodo_ciclo_id'),
                    0,
                );

                if ($base === null) {
                    abort(404);
                }

                if (! $base->asignatura->califica_por_destrezas) {
                    $validator->errors()->add(
                        'establecimiento_asignatura_id',
                        'Esta asignatura no se evalúa por destrezas.',
                    );

                    return;
                }

                $this->contexto = new ContextoDestreza($base->aula, $base->asignatura, $base->ciclo);
            },
        ];
    }

    public function contexto(): ContextoDestreza
    {
        if (! $this->contexto instanceof ContextoDestreza) {
            abort(404);
        }

        return $this->contexto;
    }
}
