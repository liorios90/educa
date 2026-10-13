<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateConfiguracionAsignaturasAulaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'configuraciones' => ['required', 'array'],
            'configuraciones.*' => ['required', 'array:personalizada,ciclos'],
            'configuraciones.*.personalizada' => ['required', 'boolean'],
            'configuraciones.*.ciclos' => ['nullable', 'array'],
            'configuraciones.*.ciclos.*' => ['required', 'array:porcentaje_insumos,porcentaje_examen,porcentaje_proyecto'],
            'configuraciones.*.ciclos.*.porcentaje_insumos' => ['required', 'numeric', 'min:0', 'max:100'],
            'configuraciones.*.ciclos.*.porcentaje_examen' => ['required', 'numeric', 'min:0', 'max:100'],
            'configuraciones.*.ciclos.*.porcentaje_proyecto' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $configuraciones = $this->input('configuraciones', []);

            if (! is_array($configuraciones)) {
                return;
            }

            foreach ($configuraciones as $asignaturaId => $configuracion) {
                if (! is_array($configuracion) || ! filter_var($configuracion['personalizada'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }

                $ciclos = $configuracion['ciclos'] ?? [];

                if (! is_array($ciclos)) {
                    continue;
                }

                foreach ($ciclos as $orden => $ciclo) {
                    if (! is_array($ciclo) || ! isset($ciclo['porcentaje_insumos'], $ciclo['porcentaje_examen'], $ciclo['porcentaje_proyecto'])) {
                        continue;
                    }

                    $suma = round(
                        (float) $ciclo['porcentaje_insumos']
                        + (float) $ciclo['porcentaje_examen']
                        + (float) $ciclo['porcentaje_proyecto'],
                        2,
                    );

                    if (abs($suma - 100.0) > 0.009) {
                        $validator->errors()->add(
                            "configuraciones.{$asignaturaId}.ciclos.{$orden}",
                            'Los porcentajes de insumos, examen y proyecto de cada ciclo deben sumar 100%.',
                        );
                    }
                }
            }
        }];
    }
}
