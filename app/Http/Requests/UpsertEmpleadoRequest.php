<?php

namespace App\Http\Requests;

use App\Auth\ActiveOferta;
use App\Enums\Role;
use App\Models\Empleado;
use App\Models\EstablecimientoAsignatura;
use App\Models\Sys_Provincia;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpsertEmpleadoRequest extends FormRequest
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
            'tipo_contrato_id' => ['required', 'integer', 'exists:sys_tipo_contratos,id'],
            'cargo_id' => ['nullable', 'integer', 'min:1'],
            'funcion_id' => ['nullable', 'integer', 'exists:sys_funciones,id'],
            'horas' => ['required', 'integer', 'min:0'],
            'anios_experiencia' => ['required', 'integer', 'min:0'],
            'anios_instituto' => ['required', 'integer', 'min:0'],
            'contacto_emergencia' => ['required', 'string', 'max:255'],
            'contacto_num' => ['required', 'string', 'max:255'],
            'asignaturas' => ['array'],
            'asignaturas.*' => ['integer', 'distinct'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['distinct', Rule::in(array_map(
                fn (Role $role): string => $role->value,
                Role::assignableToEmpleado(),
            ))],
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
            'asignaturas' => $this->marcaDocente()
                ? collect($this->input('asignaturas', []))
                    ->filter(fn (mixed $id): bool => is_numeric($id))
                    ->map(fn (mixed $id): int => (int) $id)
                    ->unique()
                    ->values()
                    ->all()
                : [],
            'lote' => $this->filled('lote') ? $this->integer('lote') : null,
            'cargo_id' => $this->filled('cargo_id') ? $this->integer('cargo_id') : null,
            'funcion_id' => $this->filled('funcion_id') ? $this->integer('funcion_id') : null,
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
                if (! $this->boolean('activo') || ! $this->marcaDocente()) {
                    return;
                }

                $permitidas = $this->especialidadesDeLaSesion();

                if ($permitidas === []) {
                    $validator->errors()->add(
                        'asignaturas',
                        'Define la malla de esta jornada antes de registrar la especialidad del docente.',
                    );

                    return;
                }

                $elegidas = array_map(
                    fn (mixed $id): int => (int) $id,
                    (array) $this->input('asignaturas', []),
                );

                if ($elegidas === [] || array_diff($elegidas, $permitidas) !== []) {
                    $validator->errors()->add(
                        'asignaturas',
                        'Marca las asignaturas de la especialidad del docente.',
                    );
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
            'email.required' => 'Indica el correo del empleado.',
            'password.required' => 'Indica la contraseña del empleado.',
            'tipo_contrato_id.required' => 'Selecciona el tipo de contrato.',
            'roles.required' => 'Selecciona al menos un rol.',
            'roles.min' => 'Selecciona al menos un rol.',
            'asignaturas.*.integer' => 'Marca las asignaturas de la especialidad del docente.',
        ];
    }

    public function esDocente(): bool
    {
        return $this->marcaDocente();
    }

    /**
     * @return list<Role>
     */
    public function selectedRoles(): array
    {
        return array_values(array_filter(
            array_map(
                fn (mixed $value): ?Role => Role::tryFrom((string) $value),
                (array) $this->validated('roles'),
            ),
            fn (?Role $role): bool => $role instanceof Role && $role->isAssignableToEmpleado(),
        ));
    }

    private function marcaDocente(): bool
    {
        return in_array(Role::Docente->value, array_map(
            fn (mixed $role): string => (string) $role,
            (array) $this->input('roles', []),
        ), true);
    }

    /**
     * @return list<int>
     */
    private function especialidadesDeLaSesion(): array
    {
        $user = $this->user();
        $oferta = $this->container->make(ActiveOferta::class)->get($user);

        if ($user?->establecimiento_id === null || $oferta === null) {
            return [];
        }

        return EstablecimientoAsignatura::query()
            ->where('establecimiento_id', $user->establecimiento_id)
            ->where('establecimiento_modalidad_jornada_id', $oferta->id)
            ->distinct()
            ->orderBy('asignatura_id')
            ->pluck('asignatura_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    private function personaId(): ?int
    {
        $empleado = $this->route('empleado');

        if (! $empleado instanceof Empleado) {
            return null;
        }

        return $empleado->persona_id;
    }

    private function cuenta(): ?User
    {
        $empleado = $this->route('empleado');

        if (! $empleado instanceof Empleado) {
            return null;
        }

        return $empleado->persona?->user;
    }
}
