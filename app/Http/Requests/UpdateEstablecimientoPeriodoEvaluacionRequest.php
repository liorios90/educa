<?php

namespace App\Http\Requests;

use App\Auth\ActivePeriodo;
use App\Enums\EsquemaCiclo;
use App\Enums\Role;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEstablecimientoPeriodoEvaluacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasRole(Role::Admin)
            && $user->establecimiento_id !== null
            && $this->periodo() !== null;
    }

    protected function prepareForValidation(): void
    {
        $ciclos = [];

        foreach (array_values($this->input('ciclos', [])) as $ciclo) {
            $tieneExamen = $this->toBoolean($ciclo['tiene_examen'] ?? false);
            $tieneProyecto = $this->toBoolean($ciclo['tiene_proyecto'] ?? false);

            $ciclos[] = [
                ...$ciclo,
                'tiene_examen' => $tieneExamen,
                'tiene_proyecto' => $tieneProyecto,
                'porcentaje_examen' => $tieneExamen ? ($ciclo['porcentaje_examen'] ?? '0') : '0',
                'porcentaje_proyecto' => $tieneProyecto ? ($ciclo['porcentaje_proyecto'] ?? '0') : '0',
            ];
        }

        $this->merge([
            'tiene_examen_final' => $this->boolean('tiene_examen_final'),
            'tiene_proyecto_final' => $this->boolean('tiene_proyecto_final'),
            'porcentaje_examen_final' => $this->boolean('tiene_examen_final')
                ? $this->input('porcentaje_examen_final', '0')
                : '0',
            'porcentaje_proyecto_final' => $this->boolean('tiene_proyecto_final')
                ? $this->input('porcentaje_proyecto_final', '0')
                : '0',
            'ciclos' => $ciclos,
        ]);
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'esquema_ciclo' => ['required', Rule::enum(EsquemaCiclo::class)],
            'numero_parciales' => ['required', 'integer', 'min:1', 'max:6'],
            'tiene_examen_final' => ['required', 'boolean'],
            'tiene_proyecto_final' => ['required', 'boolean'],
            'porcentaje_examen_final' => ['required', 'numeric', 'min:0', 'max:100'],
            'porcentaje_proyecto_final' => ['required', 'numeric', 'min:0', 'max:100'],
            'ciclos' => ['required', 'array', 'min:1', 'max:6'],
            'ciclos.*.nombre' => ['required', 'string', 'max:255'],
            'ciclos.*.porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'ciclos.*.porcentaje_insumos' => ['required', 'numeric', 'min:0', 'max:100'],
            'ciclos.*.tiene_examen' => ['required', 'boolean'],
            'ciclos.*.porcentaje_examen' => ['required', 'numeric', 'min:0', 'max:100'],
            'ciclos.*.tiene_proyecto' => ['required', 'boolean'],
            'ciclos.*.porcentaje_proyecto' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'esquema_ciclo.required' => 'Selecciona si el periodo se trabaja en quimestres, trimestres u otro esquema.',
            'numero_parciales.required' => 'Indica cuántos parciales se van a trabajar.',
            'numero_parciales.min' => 'Debe haber al menos un parcial.',
            'numero_parciales.max' => 'No se pueden configurar más de 6 parciales.',
            'ciclos.required' => 'Configura los ciclos del periodo.',
            'ciclos.min' => 'Debe haber al menos un ciclo.',
            'ciclos.max' => 'No se pueden configurar más de 6 ciclos.',
            'ciclos.*.nombre.required' => 'Indica el nombre del ciclo.',
            'ciclos.*.porcentaje.required' => 'Indica el porcentaje del ciclo en el periodo.',
            'ciclos.*.porcentaje_insumos.required' => 'Indica el porcentaje de insumos del ciclo.',
            'ciclos.*.porcentaje_examen.required' => 'Indica el porcentaje del examen del ciclo.',
            'ciclos.*.porcentaje_proyecto.required' => 'Indica el porcentaje del proyecto del ciclo.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $esquema = EsquemaCiclo::from($this->string('esquema_ciclo')->toString());
                $ciclos = array_values($this->input('ciclos', []));
                $cantidadFija = $esquema->cantidadFija();

                if ($cantidadFija !== null && count($ciclos) !== $cantidadFija) {
                    $validator->errors()->add(
                        'ciclos',
                        $esquema === EsquemaCiclo::Quimestres
                            ? 'Los quimestres deben tener 2 ciclos.'
                            : 'Los trimestres deben tener 3 ciclos.',
                    );

                    return;
                }

                $sumaPeriodo = round(array_sum(array_map(
                    fn (array $ciclo): float => (float) $ciclo['porcentaje'],
                    $ciclos,
                )) + (float) $this->input('porcentaje_examen_final') + (float) $this->input('porcentaje_proyecto_final'), 2);

                if (abs($sumaPeriodo - 100.0) > 0.009) {
                    $validator->errors()->add(
                        'totales',
                        'Los ciclos, el examen final y el proyecto final deben sumar 100%.',
                    );
                }

                if ($this->boolean('tiene_examen_final') && (float) $this->input('porcentaje_examen_final') <= 0.009) {
                    $validator->errors()->add(
                        'porcentaje_examen_final',
                        'Indica el porcentaje del examen final del periodo.',
                    );
                }

                if ($this->boolean('tiene_proyecto_final') && (float) $this->input('porcentaje_proyecto_final') <= 0.009) {
                    $validator->errors()->add(
                        'porcentaje_proyecto_final',
                        'Indica el porcentaje del proyecto final del periodo.',
                    );
                }

                foreach ($ciclos as $indice => $ciclo) {
                    $insumos = (float) $ciclo['porcentaje_insumos'];
                    $examen = (float) $ciclo['porcentaje_examen'];
                    $proyecto = (float) $ciclo['porcentaje_proyecto'];

                    if ($this->toBoolean($ciclo['tiene_examen'] ?? false) && $examen <= 0.009) {
                        $validator->errors()->add(
                            'ciclos.'.$indice.'.porcentaje_examen',
                            'Indica el porcentaje del examen del ciclo.',
                        );
                    }

                    if ($this->toBoolean($ciclo['tiene_proyecto'] ?? false) && $proyecto <= 0.009) {
                        $validator->errors()->add(
                            'ciclos.'.$indice.'.porcentaje_proyecto',
                            'Indica el porcentaje del proyecto del ciclo.',
                        );
                    }

                    if (abs(round($insumos + $examen + $proyecto, 2) - 100.0) > 0.009) {
                        $validator->errors()->add(
                            'ciclos.'.$indice.'.porcentaje_insumos',
                            'Los insumos, el examen y el proyecto del ciclo deben sumar 100%.',
                        );
                    }
                }
            },
        ];
    }

    public function periodo(): ?EstablecimientoPeriodo
    {
        return $this->container->make(ActivePeriodo::class)->get($this->user());
    }

    /**
     * @return list<array{nombre: string, porcentaje: string, porcentaje_insumos: string, porcentaje_examen: string, porcentaje_proyecto: string}>
     */
    public function ciclos(): array
    {
        $ciclos = [];

        foreach (array_values($this->validated('ciclos')) as $ciclo) {
            $ciclos[] = [
                'nombre' => $ciclo['nombre'],
                'porcentaje' => number_format((float) $ciclo['porcentaje'], 2, '.', ''),
                'porcentaje_insumos' => number_format((float) $ciclo['porcentaje_insumos'], 2, '.', ''),
                'porcentaje_examen' => number_format((float) $ciclo['porcentaje_examen'], 2, '.', ''),
                'porcentaje_proyecto' => number_format((float) $ciclo['porcentaje_proyecto'], 2, '.', ''),
            ];
        }

        return $ciclos;
    }

    public function esquema(): EsquemaCiclo
    {
        return EsquemaCiclo::from($this->string('esquema_ciclo')->toString());
    }

    public function porcentajeExamenFinal(): string
    {
        return number_format((float) $this->validated('porcentaje_examen_final'), 2, '.', '');
    }

    public function porcentajeProyectoFinal(): string
    {
        return number_format((float) $this->validated('porcentaje_proyecto_final'), 2, '.', '');
    }

    private function toBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
