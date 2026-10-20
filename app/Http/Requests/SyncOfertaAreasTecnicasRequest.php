<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncOfertaAreasTecnicasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ($this->user()?->hasRole(Role::Admin) ?? false)
            && ($this->user()?->establecimiento?->bachillerato_tecnico ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'areas_tecnicas' => ['sometimes', 'array'],
            'areas_tecnicas.*' => ['integer', 'distinct', 'exists:sys_areas,id'],
        ];
    }
}
