<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Role::Admin, Role::Sistemas]) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['distinct', Rule::enum(Role::class)],
            'establecimiento_id' => [
                Rule::requiredIf(fn (): bool => $this->selectedRolesRequireEstablecimiento()),
                'nullable',
                'integer',
                Rule::exists('establecimientos', 'id'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'roles.required' => 'Selecciona al menos un rol.',
            'establecimiento_id.required' => 'Este rol debe pertenecer a un establecimiento.',
        ];
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
        ));
    }

    private function selectedRolesRequireEstablecimiento(): bool
    {
        foreach ((array) $this->input('roles', []) as $value) {
            if (Role::tryFrom((string) $value)?->requiresEstablecimiento()) {
                return true;
            }
        }

        return false;
    }
}
