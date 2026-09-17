<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Sys_Grado;
use App\Models\Sys_Subnivel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertEstructuraGradoRequest extends FormRequest
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
        $grado = $this->route('grado');
        $ignoreId = $grado instanceof Sys_Grado ? $grado->id : null;

        return [
            'nombre' => ['required', 'string', 'max:50', Rule::unique('sys_grados', 'nombre')->ignore($ignoreId)],
            'siglas' => ['nullable', 'string', 'max:10'],
            'descripcion' => ['nullable', 'string', 'max:150'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $grado = $this->route('grado');
        $subnivel = $this->route('subnivel');

        if ($grado instanceof Sys_Grado) {
            $this->errorBag = 'grado-'.$grado->id;

            return;
        }

        $this->errorBag = 'grado-create-'.($subnivel instanceof Sys_Subnivel ? $subnivel->id : 'new');
    }
}
