<?php

namespace App\Http\Requests\Auth;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectActiveRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        $assigned = $this->user()?->getRoleNames()->all() ?? [];

        return [
            'role' => ['required', Rule::enum(Role::class), Rule::in($assigned)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.required' => 'Selecciona el rol con el que quieres entrar.',
            'role.in' => 'No puedes entrar con un rol que no tienes asignado.',
        ];
    }
}
