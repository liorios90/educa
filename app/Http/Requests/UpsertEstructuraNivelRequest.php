<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Sys_Nivel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertEstructuraNivelRequest extends FormRequest
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
        $nivel = $this->route('nivel');
        $ignoreId = $nivel instanceof Sys_Nivel ? $nivel->id : null;

        return [
            'nombre' => ['required', 'string', 'max:50', Rule::unique('sys_niveles', 'nombre')->ignore($ignoreId)],
            'siglas' => ['nullable', 'string', 'max:10'],
            'descripcion' => ['nullable', 'string', 'max:150'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $nivel = $this->route('nivel');
        $this->errorBag = $nivel instanceof Sys_Nivel ? 'nivel-'.$nivel->id : 'nivel-create';
    }
}
