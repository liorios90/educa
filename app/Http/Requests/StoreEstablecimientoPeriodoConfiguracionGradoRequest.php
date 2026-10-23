<?php

namespace App\Http\Requests;

use App\Auth\ActivePeriodo;
use App\Models\EstablecimientoPeriodoConfiguracionGrado;
use App\Services\GradosConPonderacionCuantitativa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEstablecimientoPeriodoConfiguracionGradoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $periodo = $this->container->make(ActivePeriodo::class)->get($this->user());
        $configuracion = $this->route('configuracionGrado');

        return $periodo !== null
            && ! $periodo->estaAbierto()
            && $periodo->ciclos()->exists()
            && (! $configuracion instanceof EstablecimientoPeriodoConfiguracionGrado
                || (int) $configuracion->establecimiento_periodo_id === (int) $periodo->id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $periodo = $this->container->make(ActivePeriodo::class)->get($this->user());
        $grados = $this->container->make(GradosConPonderacionCuantitativa::class)
            ->paraOferta($periodo?->establecimiento_modalidad_jornada_id);
        $configuracion = $this->route('configuracionGrado');
        $nombre = Rule::unique('establecimiento_periodo_configuraciones_grado', 'nombre')
            ->where('establecimiento_periodo_id', $periodo?->id);

        if ($configuracion instanceof EstablecimientoPeriodoConfiguracionGrado) {
            $nombre->ignore($configuracion->id);
        }

        $ciclos = $periodo?->ciclos ?? collect();
        $reglas = [
            'nombre' => ['required', 'string', 'max:120', $nombre],
            'grados' => ['required', 'array', 'min:1'],
            'grados.*' => ['required', 'integer', 'distinct', Rule::in($grados->keys()->all())],
            'ponderaciones_periodo' => ['required', 'array:'.implode(',', $ciclos->pluck('orden')->all())],
            'porcentaje_examen_final' => ['required', 'numeric', 'min:0', 'max:100'],
            'porcentaje_proyecto_final' => ['required', 'numeric', 'min:0', 'max:100'],
            'ciclos' => ['required', 'array:'.implode(',', $ciclos->pluck('orden')->all())],
        ];

        foreach ($ciclos as $ciclo) {
            $reglas['ponderaciones_periodo.'.$ciclo->orden] = ['required', 'numeric', 'min:0', 'max:100'];
            $reglas['ciclos.'.$ciclo->orden] = ['required', 'array:porcentaje_insumos,porcentaje_examen,porcentaje_proyecto'];
            $reglas['ciclos.'.$ciclo->orden.'.porcentaje_insumos'] = ['required', 'numeric', 'min:0', 'max:100'];
            $reglas['ciclos.'.$ciclo->orden.'.porcentaje_examen'] = ['required', 'numeric', 'min:0', 'max:100'];
            $reglas['ciclos.'.$ciclo->orden.'.porcentaje_proyecto'] = ['required', 'numeric', 'min:0', 'max:100'];
        }

        return $reglas;
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['nombre', 'grados'])) {
                return;
            }

            $periodo = $this->container->make(ActivePeriodo::class)->get($this->user());

            if ($periodo === null) {
                return;
            }

            $ponderacionesPeriodo = $this->input('ponderaciones_periodo', []);
            $porcentajeFinal = (float) $this->input('porcentaje_examen_final') + (float) $this->input('porcentaje_proyecto_final');
            $sumaPeriodo = round(array_sum(array_map('floatval', is_array($ponderacionesPeriodo) ? $ponderacionesPeriodo : [])) + $porcentajeFinal, 2);

            if (abs($sumaPeriodo - 100.0) > 0.009) {
                $validator->errors()->add('ponderaciones_periodo', 'La ponderación de los ciclos, el examen final y el proyecto final deben sumar 100%.');
            }

            $configuracionActual = $this->route('configuracionGrado');
            $gradosExistentes = array_map(
                'strval',
                EstablecimientoPeriodoConfiguracionGrado::gradoIdsOcupados(
                    (int) $periodo->id,
                    $configuracionActual instanceof EstablecimientoPeriodoConfiguracionGrado
                        ? (int) $configuracionActual->id
                        : null,
                ),
            );

            if (array_intersect(array_map('strval', $this->input('grados', [])), $gradosExistentes) !== []) {
                $validator->errors()->add('grados', 'Un grado seleccionado ya pertenece a otra configuración.');
            }

            foreach ($this->input('ciclos', []) as $orden => $componentes) {
                if (! is_array($componentes)) {
                    continue;
                }

                $suma = round(array_sum(array_map('floatval', $componentes)), 2);

                if (abs($suma - 100.0) > 0.009) {
                    $validator->errors()->add('ciclos.'.$orden, 'Los porcentajes de insumos, examen y proyecto de cada ciclo deben sumar 100%.');
                }
            }
        }];
    }
}
