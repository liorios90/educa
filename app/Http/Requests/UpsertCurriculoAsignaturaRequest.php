<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertCurriculoAsignaturaRequest extends FormRequest
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
        $asignatura = $this->route('asignatura');
        $area = $this->route('area');
        $ignoreId = $asignatura instanceof Sys_Asignatura ? $asignatura->id : null;
        $areaId = $area instanceof Sys_Area ? $area->id : null;

        return [
            'codigo' => ['nullable', 'string', 'max:20'],
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('sys_asignaturas', 'nombre')
                    ->where('area_id', $areaId)
                    ->ignore($ignoreId),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'orden' => ['required', 'integer', 'min:0', 'max:999'],
            'horas_semanales' => ['nullable', 'integer', 'min:0', 'max:40'],
            'aparece_en_libreta' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $asignatura = $this->route('asignatura');
        $area = $this->route('area');

        $this->merge([
            'aparece_en_libreta' => $this->boolean('aparece_en_libreta'),
            'orden' => $this->filled('orden') ? $this->integer('orden') : 0,
            'horas_semanales' => $this->filled('horas_semanales') ? $this->integer('horas_semanales') : null,
        ]);

        if ($asignatura instanceof Sys_Asignatura) {
            $this->errorBag = 'asignatura-'.$asignatura->id;

            return;
        }

        $this->errorBag = 'asignatura-create-'.($area instanceof Sys_Area ? $area->id : 'new');
    }
}
