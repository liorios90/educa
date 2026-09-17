<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Padre;
use App\Models\Sys_Provincia;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpsertPadreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasRole(Role::Admin)
            && $user->establecimiento_id !== null;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        $personaId = $this->personaId();
        $userId = $this->cuenta()?->id;

        return [
            'tipo_identificacion_id' => ['required', 'integer', Rule::in([1, 2, 3])],
            'identificacion' => ['required', 'string', 'max:255', Rule::unique('personas', 'identificacion')->ignore($personaId)],
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'genero_id' => ['required', 'integer', Rule::in([1, 2, 3])],
            'fecha_nacimiento' => ['nullable', 'date'],
            'ciudad_nacimiento' => ['required', 'string', 'max:255'],
            'nacionalidad_id' => ['required', 'integer', 'exists:sys_paises,id'],
            'provincia_id' => ['required', 'integer', 'exists:sys_provincias,id'],
            'parroquia' => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono1' => ['required', 'string', 'max:255'],
            'telefono2' => ['required', 'string', 'max:255'],
            'lote' => ['nullable', 'integer'],
            'estado_civil_id' => ['required', 'string', 'max:255'],
            'vive_con_estudiante' => ['required', 'boolean'],
            'titulo' => ['required', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($userId),
            ],
            'password' => [
                Rule::requiredIf($this->isMethod('POST') || $this->cuenta() === null),
                'nullable',
                'confirmed',
                Password::defaults(),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'activo' => $this->boolean('activo'),
            'vive_con_estudiante' => $this->boolean('vive_con_estudiante'),
            'lote' => $this->filled('lote') ? $this->integer('lote') : null,
            'fecha_nacimiento' => $this->filled('fecha_nacimiento') ? $this->input('fecha_nacimiento') : null,
        ]);
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['nacionalidad_id', 'provincia_id'])) {
                    return;
                }

                $provincia = Sys_Provincia::query()->find($this->integer('provincia_id'));

                if ($provincia === null || (int) $provincia->pais_id !== $this->integer('nacionalidad_id')) {
                    $validator->errors()->add('provincia_id', 'La provincia no pertenece al país seleccionado.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Indica el correo del padre de familia.',
            'password.required' => 'Indica la contraseña del padre de familia.',
        ];
    }

    private function personaId(): ?int
    {
        $padre = $this->route('padre');

        if (! $padre instanceof Padre) {
            return null;
        }

        return $padre->persona_id;
    }

    private function cuenta(): ?User
    {
        $padre = $this->route('padre');

        if (! $padre instanceof Padre) {
            return null;
        }

        return $padre->persona?->user;
    }
}
