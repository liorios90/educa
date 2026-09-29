<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertEstablecimientoPeriodoRequest extends FormRequest
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
        return [
            'establecimiento_modalidad_jornada_id' => [
                'required',
                'integer',
                Rule::exists('establecimiento_modalidad_jornadas', 'id'),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'activo' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'establecimiento_modalidad_jornada_id.required' => 'Selecciona la modalidad y jornada del periodo.',
            'establecimiento_modalidad_jornada_id.exists' => 'La modalidad y jornada seleccionadas no existen.',
            'nombre.required' => 'Indica el nombre del periodo que verán los usuarios.',
            'fecha_inicio.required' => 'Indica la fecha de inicio del periodo.',
            'fecha_fin.required' => 'Indica la fecha de fin del periodo.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('establecimiento_modalidad_jornada_id')) {
                    return;
                }

                $establecimiento = $this->route('establecimiento');

                if (! $establecimiento instanceof Establecimiento) {
                    return;
                }

                $oferta = EstablecimientoModalidadJornada::query()
                    ->with('establecimientoModalidad')
                    ->find($this->integer('establecimiento_modalidad_jornada_id'));

                if ($oferta === null) {
                    return;
                }

                if ((int) $oferta->establecimientoModalidad->establecimiento_id !== (int) $establecimiento->id) {
                    $validator->errors()->add(
                        'establecimiento_modalidad_jornada_id',
                        'La modalidad y jornada no pertenecen a este establecimiento.',
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $periodo = $this->route('periodo');

        $this->errorBag = $periodo instanceof EstablecimientoPeriodo
            ? 'periodo-'.$periodo->id
            : 'periodo-create-'.($this->input('establecimiento_modalidad_jornada_id') ?: 'new');

        $this->merge([
            'activo' => $this->boolean('activo'),
        ]);
    }
}
