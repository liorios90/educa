<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Enums\TipoCalificacion;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertEstructuraSubnivelRequest extends FormRequest
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
        $subnivel = $this->route('subnivel');
        $ignoreId = $subnivel instanceof Sys_Subnivel ? $subnivel->id : null;

        return [
            'nombre' => ['required', 'string', 'max:50', Rule::unique('sys_subniveles', 'nombre')->ignore($ignoreId)],
            'siglas' => ['nullable', 'string', 'max:10'],
            'descripcion' => ['nullable', 'string', 'max:150'],
            'tipo_calificacion' => ['required', Rule::enum(TipoCalificacion::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $subnivel = $this->route('subnivel');
        $nivel = $this->route('nivel');

        if ($subnivel instanceof Sys_Subnivel) {
            $this->errorBag = 'subnivel-'.$subnivel->id;

            return;
        }

        $this->errorBag = 'subnivel-create-'.($nivel instanceof Sys_Nivel ? $nivel->id : 'new');
    }
}
