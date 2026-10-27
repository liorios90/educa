<?php

namespace App\Http\Requests;

use App\Auth\ContextoAsistencia;
use App\Enums\ModoAsistencia;
use App\Enums\Role;
use App\Services\AsignacionesDocente;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

class StoreAsistenciaContextoRequest extends FormRequest
{
    private ?ContextoAsistencia $contexto = null;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasRole(Role::Docente);
    }

    /**
     * @return array<string, list<ValidationRule|RequiredIf|string>>
     */
    public function rules(): array
    {
        return [
            'modo' => ['required', Rule::enum(ModoAsistencia::class)],
            'establecimiento_aula_id' => ['required', 'integer'],
            'establecimiento_asignatura_id' => [$this->exigidoPorAsignatura(), 'nullable', 'integer'],
            'establecimiento_periodo_ciclo_id' => [$this->exigidoPorAsignatura(), 'nullable', 'integer'],
            'parcial_numero' => [$this->exigidoPorAsignatura(), 'nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'modo.required' => 'Elige si la asistencia es del día o de cada asignatura.',
            'modo.enum' => 'Elige si la asistencia es del día o de cada asignatura.',
            'establecimiento_aula_id.required' => 'Selecciona un aula.',
            'establecimiento_asignatura_id.required' => 'Selecciona una materia.',
            'establecimiento_periodo_ciclo_id.required' => 'Selecciona un quimestre o trimestre.',
            'parcial_numero.required' => 'Selecciona un parcial.',
            'parcial_numero.min' => 'Selecciona un parcial.',
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

                $contexto = $this->asignaciones()->contextoAsistencia(
                    $user,
                    (int) $this->input('establecimiento_aula_id'),
                    ModoAsistencia::from((string) $this->input('modo')),
                    $this->entero('establecimiento_asignatura_id'),
                    $this->entero('establecimiento_periodo_ciclo_id'),
                    $this->entero('parcial_numero'),
                );

                if ($contexto === null) {
                    abort(404);
                }

                $this->contexto = $contexto;
            },
        ];
    }

    public function contexto(): ContextoAsistencia
    {
        if (! $this->contexto instanceof ContextoAsistencia) {
            abort(404);
        }

        return $this->contexto;
    }

    private function exigidoPorAsignatura(): RequiredIf
    {
        return Rule::requiredIf(fn (): bool => $this->input('modo') !== ModoAsistencia::Jornada->value);
    }

    private function entero(string $campo): ?int
    {
        $valor = $this->input($campo);

        return is_numeric($valor) ? (int) $valor : null;
    }

    private function asignaciones(): AsignacionesDocente
    {
        return $this->container->make(AsignacionesDocente::class);
    }
}
