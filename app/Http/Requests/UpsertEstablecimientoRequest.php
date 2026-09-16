<?php

namespace App\Http\Requests;

use App\Enums\Regimen;
use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\Sys_Circuito;
use App\Models\Sys_Distrito;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertEstablecimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::Sistemas) ?? false;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        $establecimiento = $this->route('establecimiento');
        $ignoreId = $establecimiento instanceof Establecimiento ? $establecimiento->id : null;

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:255'],
            'representante' => ['required', 'string', 'max:255'],
            'codigo_amie' => ['required', 'string', 'max:255', Rule::unique('establecimientos', 'codigo_amie')->ignore($ignoreId)],
            'regimen' => ['required', Rule::enum(Regimen::class)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('establecimientos', 'email')->ignore($ignoreId)],
            'usuario' => ['required', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
            'logo' => [
                Rule::requiredIf($this->isMethod('POST')),
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'grupo_amie' => ['nullable', 'integer'],
            'zona_id' => ['required', 'integer', 'exists:sys_zonas,id'],
            'distrito_id' => ['required', 'integer', 'exists:sys_distritos,id'],
            'circuito_id' => ['required', 'integer', 'exists:sys_circuitos,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'activo' => $this->boolean('activo'),
            'grupo_amie' => $this->filled('grupo_amie') ? $this->integer('grupo_amie') : null,
        ]);
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['zona_id', 'distrito_id', 'circuito_id'])) {
                    return;
                }

                $zonaId = $this->integer('zona_id');
                $distritoId = $this->integer('distrito_id');
                $circuitoId = $this->integer('circuito_id');

                $distrito = Sys_Distrito::query()->find($distritoId);

                if ($distrito === null || $distrito->zona_id !== $zonaId) {
                    $validator->errors()->add('distrito_id', 'El distrito no pertenece a la zona seleccionada.');
                }

                $circuito = Sys_Circuito::query()->find($circuitoId);

                if ($circuito !== null && $circuito->distrito_id !== null && $circuito->distrito_id !== $distritoId) {
                    $validator->errors()->add('circuito_id', 'El circuito no pertenece al distrito seleccionado.');
                }
            },
        ];
    }
}
