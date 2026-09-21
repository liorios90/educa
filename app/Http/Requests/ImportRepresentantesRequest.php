<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportRepresentantesRequest extends FormRequest
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
        return [
            'tipo' => ['required', 'string', Rule::in(['padres', 'alumnos', 'docentes'])],
            'archivo' => [
                'required',
                'file',
                'max:10240',
                'mimes:xlsx,csv,txt',
                'extensions:xlsx,csv',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo.required' => 'Selecciona si importas padres, alumnos o docentes.',
            'tipo.in' => 'Selecciona si importas padres, alumnos o docentes.',
            'archivo.required' => 'Selecciona un archivo Excel o CSV.',
            'archivo.mimes' => 'El archivo debe ser xlsx o csv.',
            'archivo.extensions' => 'El archivo debe ser xlsx o csv.',
        ];
    }
}
