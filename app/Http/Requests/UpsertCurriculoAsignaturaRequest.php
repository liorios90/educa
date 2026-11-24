<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Enums\TipoCalificacion;
use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use App\Services\AcompanamientoCivica;
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
            'tipo_calificacion' => [$this->organizadaPorAmbitos() || $this->esPeriodoCivica() ? 'nullable' : 'required', Rule::enum(TipoCalificacion::class)],
            'usa_criterios' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array{codigo?: string|null, nombre: string, descripcion?: string|null, orden: int, horas_semanales?: int|null, aparece_en_libreta: bool, tipo_calificacion: string, usa_criterios: bool}
     */
    public function asignaturaAttributes(): array
    {
        /** @var array{codigo?: string|null, nombre: string, descripcion?: string|null, orden: int, horas_semanales?: int|null, aparece_en_libreta: bool} $attributes */
        $attributes = $this->safe()->only([
            'codigo',
            'nombre',
            'descripcion',
            'orden',
            'horas_semanales',
            'aparece_en_libreta',
        ]);

        if ($this->esPeriodoCivica()) {
            $attributes['tipo_calificacion'] = TipoCalificacion::Cualitativa->value;
            $attributes['usa_criterios'] = false;

            return $attributes;
        }

        if ($this->organizadaPorAmbitos()) {
            $attributes['tipo_calificacion'] = TipoCalificacion::Cualitativa->value;
            $attributes['usa_criterios'] = true;

            return $attributes;
        }

        $tipo = TipoCalificacion::from((string) $this->input('tipo_calificacion'));
        $attributes['tipo_calificacion'] = $tipo->value;
        $attributes['usa_criterios'] = $tipo === TipoCalificacion::Cualitativa && $this->boolean('usa_criterios');

        return $attributes;
    }

    private function esPeriodoCivica(): bool
    {
        return $this->input('nombre') === AcompanamientoCivica::ASIGNATURA;
    }

    private function organizadaPorAmbitos(): bool
    {
        $area = $this->route('area');

        return $area instanceof Sys_Area && (bool) $area->subnivel?->usa_ambitos;
    }

    protected function prepareForValidation(): void
    {
        $asignatura = $this->route('asignatura');
        $area = $this->route('area');

        $this->merge([
            'aparece_en_libreta' => $this->boolean('aparece_en_libreta'),
            'usa_criterios' => $this->boolean('usa_criterios'),
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
