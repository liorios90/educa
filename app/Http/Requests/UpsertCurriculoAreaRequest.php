<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Sys_Area;
use App\Models\Sys_Subnivel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertCurriculoAreaRequest extends FormRequest
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
        $area = $this->route('area');
        $subnivel = $this->route('subnivel');
        $ignoreId = $area instanceof Sys_Area ? $area->id : null;
        $subnivelId = $subnivel instanceof Sys_Subnivel ? $subnivel->id : null;

        return [
            'codigo' => ['nullable', 'string', 'max:20'],
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('sys_areas', 'nombre')
                    ->where('subnivel_id', $subnivelId)
                    ->ignore($ignoreId),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'orden' => ['required', 'integer', 'min:0', 'max:999'],
            'aparece_en_libreta' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $area = $this->route('area');
        $subnivel = $this->route('subnivel');

        $this->merge([
            'aparece_en_libreta' => $this->boolean('aparece_en_libreta'),
            'orden' => $this->filled('orden') ? $this->integer('orden') : 0,
        ]);

        if ($area instanceof Sys_Area) {
            $this->errorBag = 'area-'.$area->id;

            return;
        }

        $this->errorBag = 'area-create-'.($subnivel instanceof Sys_Subnivel ? $subnivel->id : 'new');
    }
}
