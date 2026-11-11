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
            'califica_por_destrezas' => ['nullable', 'boolean'],
            'usa_ambitos' => ['nullable', 'boolean'],
            'equivale_nota_nacional' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array{nombre: string, siglas?: string|null, descripcion?: string|null, tipo_calificacion: string, califica_por_destrezas: bool, usa_ambitos: bool, equivale_nota_nacional: bool}
     */
    public function subnivelAttributes(): array
    {
        /** @var array{nombre: string, siglas?: string|null, descripcion?: string|null, tipo_calificacion: string} $attributes */
        $attributes = $this->safe()->only(['nombre', 'siglas', 'descripcion', 'tipo_calificacion']);
        $tipo = TipoCalificacion::from($attributes['tipo_calificacion']);
        $porDestrezas = $tipo === TipoCalificacion::Cualitativa
            && $this->boolean('califica_por_destrezas');
        $attributes['califica_por_destrezas'] = $porDestrezas;
        $attributes['usa_ambitos'] = $porDestrezas && $this->boolean('usa_ambitos');
        $attributes['equivale_nota_nacional'] = $porDestrezas && $this->boolean('equivale_nota_nacional');

        return $attributes;
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
