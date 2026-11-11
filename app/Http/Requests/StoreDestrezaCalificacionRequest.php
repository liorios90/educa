<?php

namespace App\Http\Requests;

use App\Auth\ContextoDestreza;
use App\Enums\CalificacionDestreza;
use App\Enums\Role;
use App\Services\AsignacionesDocente;
use App\Services\RegistroDestrezas;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class StoreDestrezaCalificacionRequest extends FormRequest
{
    private ?ContextoDestreza $contexto = null;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasRole(Role::Docente);
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'establecimiento_aula_id' => ['required', 'integer'],
            'establecimiento_asignatura_id' => ['required', 'integer'],
            'establecimiento_periodo_ciclo_id' => ['required', 'integer'],
            'destrezas' => ['required', 'array'],
            'destrezas.*' => ['array'],
            'destrezas.*.*' => ['array'],
            'destrezas.*.*.calificacion' => ['required', 'string', Rule::enum(CalificacionDestreza::class)],
            'destrezas.*.*.observacion' => ['nullable', 'string', 'max:500'],
            'destrezas.*.*.indicadores' => ['nullable', 'array'],
            'destrezas.*.*.indicadores.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'establecimiento_aula_id.required' => 'Selecciona un aula.',
            'establecimiento_asignatura_id.required' => 'Selecciona una asignatura.',
            'establecimiento_periodo_ciclo_id.required' => 'Selecciona un quimestre o trimestre.',
            'destrezas.required' => 'Registra la destreza de cada estudiante.',
            'destrezas.*.*.calificacion.required' => 'Selecciona la escala de la destreza.',
            'destrezas.*.*.calificacion.Illuminate\Validation\Rules\Enum' => 'Selecciona una opción de la escala de este subnivel.',
            'destrezas.*.*.observacion.max' => 'La recomendación no puede pasar de 500 caracteres.',
            'destrezas.*.*.indicadores.*.integer' => 'Marca solo indicadores de esta destreza.',
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

                $user = $this->user();

                if ($user === null) {
                    abort(404);
                }

                $contexto = $this->container->make(AsignacionesDocente::class)->contextoDestreza(
                    $user,
                    (int) $this->input('establecimiento_aula_id'),
                    (int) $this->input('establecimiento_asignatura_id'),
                    (int) $this->input('establecimiento_periodo_ciclo_id'),
                );

                if ($contexto === null) {
                    abort(404);
                }

                $this->contexto = $contexto;
                $this->validarLista($validator, $contexto);
            },
        ];
    }

    public function contexto(): ContextoDestreza
    {
        if (! $this->contexto instanceof ContextoDestreza) {
            abort(404);
        }

        return $this->contexto;
    }

    /**
     * @return array<int, array<int, array{calificacion: string, observacion: ?string, indicadores: list<int>}>>
     */
    public function filas(): array
    {
        $filas = [];

        foreach ($this->validated('destrezas') as $alumnoId => $indicadores) {
            foreach ($indicadores as $indicadorId => $fila) {
                $observacion = $fila['observacion'] ?? null;
                $marcados = $fila['indicadores'] ?? [];

                $filas[(int) $alumnoId][(int) $indicadorId] = [
                    'calificacion' => (string) $fila['calificacion'],
                    'observacion' => is_string($observacion) && trim($observacion) !== '' ? trim($observacion) : null,
                    'indicadores' => collect(is_array($marcados) ? $marcados : [])
                        ->map(fn (mixed $id): int => (int) $id)
                        ->unique()
                        ->values()
                        ->all(),
                ];
            }
        }

        return $filas;
    }

    private function validarLista(Validator $validator, ContextoDestreza $contexto): void
    {
        $roster = $this->container->make(AsignacionesDocente::class)
            ->alumnosDe($contexto->aula, $contexto->asignatura)
            ->map(fn ($alumno): int => (int) $alumno->id)
            ->sort()
            ->values();

        if ($roster->isEmpty()) {
            $validator->errors()->add('destrezas', 'No hay alumnos matriculados en este aula.');

            return;
        }

        $usaAmbitos = (bool) $contexto->aula->establecimientoGrado?->grado?->subnivel?->usa_ambitos;
        $registro = $this->container->make(RegistroDestrezas::class);
        $indicadores = $registro->indicadorIds($contexto);
        $indicadoresPorDestreza = $usaAmbitos ? $registro->indicadoresPorDestreza($contexto) : collect();

        if ($indicadores->isEmpty()) {
            $validator->errors()->add(
                'destrezas',
                $usaAmbitos
                    ? 'Este ámbito no tiene destrezas para el grado.'
                    : 'Esta asignatura no tiene indicadores de evaluación para el grado.',
            );

            return;
        }

        $enviados = [];

        foreach ($this->input('destrezas', []) as $alumnoId => $criterios) {
            if ((string) (int) $alumnoId !== (string) $alumnoId || ! is_array($criterios)) {
                $validator->errors()->add('destrezas', 'La lista de estudiantes no coincide con el aula. Vuelve a cargar las destrezas.');

                return;
            }

            $enviados[] = (int) $alumnoId;
            $indicadoresEnviados = [];

            foreach ($criterios as $indicadorId => $fila) {
                if ((string) (int) $indicadorId !== (string) $indicadorId || ! is_array($fila)) {
                    $validator->errors()->add('destrezas', 'La lista de indicadores no coincide con la asignatura. Vuelve a cargar las destrezas.');

                    return;
                }

                $indicadoresEnviados[] = (int) $indicadorId;
                $this->validarIndicadoresObservados($validator, $usaAmbitos, $indicadoresPorDestreza, (int) $alumnoId, (int) $indicadorId, $fila);
                $escala = CalificacionDestreza::tryFrom((string) ($fila['calificacion'] ?? ''));

                if ($escala instanceof CalificacionDestreza
                    && ($usaAmbitos ? ! $escala->permitidaEnAmbito() : ! $escala->permitidaEnElemental())) {
                    $validator->errors()->add(
                        'destrezas.'.$alumnoId.'.'.$indicadorId.'.calificacion',
                        $usaAmbitos
                            ? 'En Inicial y Preparatoria la escala va de A+ a E-, o NE si no se trabajó.'
                            : 'En Básica Elemental la escala es A, EP, I o NE.',
                    );
                }
            }

            $indicadoresEnviados = collect($indicadoresEnviados)->sort()->values();

            if ($indicadores->all() !== $indicadoresEnviados->all()) {
                $validator->errors()->add('destrezas', 'La lista de indicadores no coincide con la asignatura. Vuelve a cargar las destrezas.');

                return;
            }
        }

        $enviados = collect($enviados)->sort()->values();

        if ($roster->all() !== $enviados->all()) {
            $validator->errors()->add('destrezas', 'La lista de estudiantes no coincide con el aula. Vuelve a cargar las destrezas.');
        }
    }

    /**
     * @param  Collection<int, list<int>>  $indicadoresPorDestreza
     * @param  array<string, mixed>  $fila
     */
    private function validarIndicadoresObservados(
        Validator $validator,
        bool $usaAmbitos,
        Collection $indicadoresPorDestreza,
        int $alumnoId,
        int $destrezaId,
        array $fila,
    ): void {
        if (! $usaAmbitos || ! array_key_exists('indicadores', $fila)) {
            return;
        }

        $enviados = $fila['indicadores'];

        if (! is_array($enviados)) {
            $validator->errors()->add(
                'destrezas.'.$alumnoId.'.'.$destrezaId.'.indicadores',
                'Marca solo indicadores de esta destreza.',
            );

            return;
        }

        $permitidos = $indicadoresPorDestreza->get($destrezaId, []);

        foreach ($enviados as $indicadorId) {
            if (! in_array((int) $indicadorId, $permitidos, true)) {
                $validator->errors()->add(
                    'destrezas.'.$alumnoId.'.'.$destrezaId.'.indicadores',
                    'Marca solo indicadores de esta destreza.',
                );

                return;
            }
        }
    }
}
