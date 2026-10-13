<?php

namespace App\Http\Requests;

use App\Auth\ActivePeriodo;
use App\Enums\EsquemaCiclo;
use App\Enums\Role;
use App\Models\EstablecimientoPeriodo;
use App\Services\GradosConPonderacionCuantitativa;
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
            'nombre_esquema' => trim((string) $this->input('nombre_esquema', '')),
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
        $rules = [
            'esquema_ciclo' => ['required', Rule::enum(EsquemaCiclo::class)],
            'nombre_esquema' => ['required', 'string', 'max:255'],
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

        if ($this->input('configuracion_por_grado') !== '1') {
            return $rules;
        }

        $grados = $this->container->make(GradosConPonderacionCuantitativa::class)
            ->paraOferta($this->periodo()?->establecimiento_modalidad_jornada_id);
        $gradosSeleccionados = $this->input('grados_configurados', []);

        $rules['configuracion_por_grado'] = ['required', 'in:1'];
        $rules['grados_configurados'] = ['nullable', 'array'];
        $rules['grados_configurados.*'] = ['required', 'integer', 'distinct', Rule::in($grados->keys()->all())];
        $rules['ponderaciones_grado'] = ['sometimes', 'array:'.implode(',', array_map('strval', is_array($gradosSeleccionados) ? $gradosSeleccionados : []))];

        foreach (is_array($gradosSeleccionados) ? $gradosSeleccionados : [] as $gradoId) {
            if (! is_numeric($gradoId) || ! $grados->has((int) $gradoId)) {
                continue;
            }

            $gradeAssignments = $grados->get((int) $gradoId)['asignaturas'];
            $ciclos = $this->input('ciclos', []);
            $ciclosPermitidos = range(1, max(1, is_array($ciclos) ? count($ciclos) : 0));
            $rules['ponderaciones_grado.'.$gradoId] = ['required', 'array:'.implode(',', $gradeAssignments->pluck('id')->all())];

            foreach ($gradeAssignments as $asignatura) {
                $asignaturaId = $asignatura->id;
                $rules['ponderaciones_grado.'.$gradoId.'.'.$asignaturaId] = ['required', 'array:'.implode(',', $ciclosPermitidos)];

                foreach ($ciclosPermitidos as $orden) {
                    $rules['ponderaciones_grado.'.$gradoId.'.'.$asignaturaId.'.'.$orden] = ['required', 'numeric', 'min:0', 'max:100'];
                }
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'esquema_ciclo.required' => 'Selecciona si el periodo se trabaja en quimestres, trimestres u otro esquema.',
            'nombre_esquema.required' => 'Indica el nombre de la forma de trabajo.',
            'nombre_esquema.max' => 'El nombre de la forma de trabajo no puede superar 255 caracteres.',
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
            'grados_configurados.*.integer' => 'Selecciona un grado válido.',
            'grados_configurados.*.distinct' => 'No repitas grados en la selección.',
            'ponderaciones_grado.*.required' => 'Configura las asignaturas cuantitativas del grado seleccionado.',
            'ponderaciones_grado.*.*.*.required' => 'Indica la ponderación de cada ciclo.',
            'ponderaciones_grado.*.*.*.numeric' => 'La ponderación de cada ciclo debe ser numérica.',
            'ponderaciones_grado.*.*.*.min' => 'La ponderación no puede ser menor que cero.',
            'ponderaciones_grado.*.*.*.max' => 'La ponderación no puede superar 100%.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->periodo()?->estaAbierto()) {
                    $validator->errors()->add(
                        'periodo',
                        'Este periodo ya fue aperturado. La configuración está bloqueada y solo se puede consultar.',
                    );

                    return;
                }

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

                if ($this->input('configuracion_por_grado') === '1') {
                    $this->validarSumaPonderacionesGrado($validator);
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

    /**
     * @return list<array{establecimiento_asignatura_id: int, ciclos: list<array{orden: int, porcentaje: string}>}>|null
     */
    public function ponderacionesGrado(): ?array
    {
        if ($this->input('configuracion_por_grado') !== '1') {
            return null;
        }

        $validated = $this->validated();
        $resultado = [];

        foreach ($validated['grados_configurados'] ?? [] as $gradoId) {
            foreach ($validated['ponderaciones_grado'][$gradoId] ?? [] as $asignaturaId => $ciclos) {
                $resultado[] = [
                    'establecimiento_asignatura_id' => (int) $asignaturaId,
                    'ciclos' => collect($ciclos)
                        ->map(fn (string|int|float $porcentaje, string|int $orden): array => [
                            'orden' => (int) $orden,
                            'porcentaje' => number_format((float) $porcentaje, 2, '.', ''),
                        ])
                        ->values()
                        ->all(),
                ];
            }
        }

        return $resultado;
    }

    private function validarSumaPonderacionesGrado(Validator $validator): void
    {
        $final = (float) $this->input('porcentaje_examen_final') + (float) $this->input('porcentaje_proyecto_final');
        $ciclosPorGrado = $this->input('ponderaciones_grado', []);

        if (! is_array($ciclosPorGrado)) {
            return;
        }

        $asignaturasEsperadas = $this->container->make(GradosConPonderacionCuantitativa::class)
            ->paraOferta($this->periodo()?->establecimiento_modalidad_jornada_id);
        $gradosSeleccionados = array_map('strval', $this->input('grados_configurados', []));

        foreach ($ciclosPorGrado as $gradoId => $asignaturas) {
            if (! in_array((string) $gradoId, $gradosSeleccionados, true) || ! is_array($asignaturas)) {
                $validator->errors()->add('ponderaciones_grado', 'Configura ponderaciones únicamente para los grados seleccionados.');

                continue;
            }

            $idsPermitidos = $asignaturasEsperadas->get((int) $gradoId)['asignaturas']->pluck('id')->map(fn ($id): string => (string) $id)->all();

            foreach ($asignaturas as $asignaturaId => $ciclos) {
                if (! in_array((string) $asignaturaId, $idsPermitidos, true) || ! is_array($ciclos)) {
                    $validator->errors()->add('ponderaciones_grado', 'La configuración contiene una asignatura que no es cuantitativa o no pertenece al grado.');

                    continue;
                }

                if (count($ciclos) !== count($this->input('ciclos', []))) {
                    continue;
                }

                $suma = round(array_sum(array_map('floatval', $ciclos)) + $final, 2);

                if (abs($suma - 100.0) > 0.009) {
                    $validator->errors()->add(
                        'ponderaciones_grado.'.$gradoId.'.'.$asignaturaId,
                        'Las ponderaciones de los ciclos de cada asignatura, junto con el examen y proyecto final, deben sumar 100%.',
                    );
                }
            }
        }
    }

    private function toBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
