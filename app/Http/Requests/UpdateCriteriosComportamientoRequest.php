<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCriteriosComportamientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::Admin) ?? false;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'criterios' => ['nullable', 'array'],
            'criterios.*' => [
                'integer',
                'distinct',
                Rule::exists('sys_criterios_comportamiento', 'id')->where('activo', true),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'criterios.*.integer' => 'Selecciona solo criterios activos del catálogo.',
            'criterios.*.distinct' => 'Cada criterio se elige una sola vez.',
            'criterios.*.exists' => 'Selecciona solo criterios activos del catálogo.',
        ];
    }
}
