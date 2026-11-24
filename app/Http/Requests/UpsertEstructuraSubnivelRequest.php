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
            'organizacion' => ['required', Rule::in(['areas', 'ambitos'])],
        ];
    }

    /**
     * @return array{nombre: string, siglas?: string|null, descripcion?: string|null, usa_ambitos: bool, tipo_calificacion?: string, califica_por_destrezas?: bool, equivale_nota_nacional?: bool}
     */
    public function subnivelAttributes(): array
    {
        $subnivel = $this->route('subnivel');
        $porAmbitos = $this->input('organizacion') === 'ambitos';

        /** @var array{nombre: string, siglas?: string|null, descripcion?: string|null, usa_ambitos: bool, tipo_calificacion?: string, califica_por_destrezas?: bool, equivale_nota_nacional?: bool} $attributes */
        $attributes = $this->safe()->only(['nombre', 'siglas', 'descripcion']);
        $attributes['usa_ambitos'] = $porAmbitos;

        if ($porAmbitos) {
            $attributes['tipo_calificacion'] = TipoCalificacion::Cualitativa->value;
            $attributes['califica_por_destrezas'] = true;
            $attributes['equivale_nota_nacional'] = $subnivel instanceof Sys_Subnivel
                && (bool) $subnivel->equivale_nota_nacional;

            return $attributes;
        }

        if (! $subnivel instanceof Sys_Subnivel) {
            $attributes['tipo_calificacion'] = TipoCalificacion::Cuantitativa->value;
            $attributes['califica_por_destrezas'] = false;
            $attributes['equivale_nota_nacional'] = false;
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'organizacion.required' => 'Elige si el subnivel se organiza por áreas o por ámbitos.',
            'organizacion.in' => 'La organización del subnivel no es válida.',
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
