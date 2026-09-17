<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Alumno;
use App\Models\Padre;
use App\Models\Sys_Provincia;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpsertAlumnoRequest extends FormRequest
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
            'contacto_emergencia' => ['required', 'string', 'max:255'],
            'padre_id' => ['nullable', 'integer', 'exists:padres,id'],
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
            'lote' => $this->filled('lote') ? $this->integer('lote') : null,
            'padre_id' => $this->filled('padre_id') ? $this->integer('padre_id') : null,
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
            function (Validator $validator): void {
                if ($validator->errors()->has('padre_id') || $this->input('padre_id') === null) {
                    return;
                }

                $padre = Padre::query()->with('persona')->find($this->integer('padre_id'));
                $establecimientoId = $this->user()?->establecimiento_id;

                if ($padre === null || (int) $padre->persona?->establecimiento_id !== (int) $establecimientoId) {
                    $validator->errors()->add('padre_id', 'Selecciona un padre de familia de este establecimiento.');
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
            'email.required' => 'Indica el correo del alumno.',
            'password.required' => 'Indica la contraseña del alumno.',
        ];
    }

    private function personaId(): ?int
    {
        $alumno = $this->route('alumno');

        if (! $alumno instanceof Alumno) {
            return null;
        }

        return $alumno->persona_id;
    }

    private function cuenta(): ?User
    {
        $alumno = $this->route('alumno');

        if (! $alumno instanceof Alumno) {
            return null;
        }

        return $alumno->persona?->user;
    }
}
