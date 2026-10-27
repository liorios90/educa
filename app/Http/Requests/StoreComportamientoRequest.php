<?php

namespace App\Http\Requests;

use App\Auth\ContextoComportamiento;
use App\Enums\Role;
use App\Models\EquivalenciaDetalle;
use App\Services\AsignacionesDocente;
use App\Services\EscalaComportamiento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class StoreComportamientoRequest extends FormRequest
{
    private ?ContextoComportamiento $contexto = null;

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
            'establecimiento_periodo_ciclo_id' => ['required', 'integer'],
            'comportamientos' => ['required', 'array'],
            'comportamientos.*' => ['array'],
            'comportamientos.*.calificacion' => ['required', 'integer'],
            'comportamientos.*.observacion' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'establecimiento_aula_id.required' => 'Selecciona un aula.',
            'establecimiento_periodo_ciclo_id.required' => 'Selecciona un quimestre o trimestre.',
            'comportamientos.required' => 'Registra el comportamiento de cada estudiante.',
            'comportamientos.*.calificacion.required' => 'Selecciona la calificación de comportamiento.',
            'comportamientos.*.calificacion.integer' => 'Selecciona una calificación de la escala del periodo.',
            'comportamientos.*.observacion.max' => 'La observación no puede pasar de 500 caracteres.',
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

                $contexto = $this->asignaciones()->contextoComportamiento(
                    $user,
                    (int) $this->input('establecimiento_aula_id'),
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

    public function contexto(): ContextoComportamiento
    {
        if (! $this->contexto instanceof ContextoComportamiento) {
            abort(404);
        }

        return $this->contexto;
    }

    /**
     * @return array<int, array{equivalencia_detalle_id: int, observacion: ?string}>
     */
    public function filas(): array
    {
        $filas = [];

        foreach ($this->validated('comportamientos') as $alumnoId => $fila) {
            $observacion = $fila['observacion'] ?? null;

            $filas[(int) $alumnoId] = [
                'equivalencia_detalle_id' => (int) $fila['calificacion'],
                'observacion' => is_string($observacion) && trim($observacion) !== '' ? trim($observacion) : null,
            ];
        }

        return $filas;
    }

    private function validarLista(Validator $validator, ContextoComportamiento $contexto): void
    {
        $roster = $this->asignaciones()->alumnosDelAula($contexto->aula)
            ->map(fn ($alumno): int => (int) $alumno->id)
            ->sort()
            ->values();

        if ($roster->isEmpty()) {
            $validator->errors()->add('comportamientos', 'No hay alumnos matriculados en este aula.');

            return;
        }

        $detalles = $this->detallesDelPeriodo((int) $contexto->aula->establecimiento_periodo_id);

        if ($detalles->isEmpty()) {
            $validator->errors()->add('comportamientos', 'El periodo no tiene una tabla de comportamiento activa.');

            return;
        }

        $enviados = [];

        foreach ($this->input('comportamientos', []) as $alumnoId => $fila) {
            if ((string) (int) $alumnoId !== (string) $alumnoId) {
                $validator->errors()->add('comportamientos', 'La lista de estudiantes no coincide con el aula. Vuelve a cargar el comportamiento.');

                return;
            }

            $enviados[] = (int) $alumnoId;
            $calificacion = is_array($fila) ? ($fila['calificacion'] ?? null) : null;
            $observacion = is_array($fila) ? trim((string) ($fila['observacion'] ?? '')) : '';
            $detalle = is_numeric($calificacion) ? $detalles->get((int) $calificacion) : null;

            if (! $detalle instanceof EquivalenciaDetalle) {
                $validator->errors()->add(
                    'comportamientos.'.$alumnoId.'.calificacion',
                    'Selecciona una calificación de la escala del periodo.',
                );

                continue;
            }

            if ($detalle->exige_observacion && $observacion === '') {
                $validator->errors()->add(
                    'comportamientos.'.$alumnoId.'.observacion',
                    'Describe el incumplimiento de los compromisos de convivencia.',
                );
            }
        }

        $enviados = collect($enviados)->sort()->values();

        if ($roster->all() !== $enviados->all()) {
            $validator->errors()->add('comportamientos', 'La lista de estudiantes no coincide con el aula. Vuelve a cargar el comportamiento.');
        }
    }

    /**
     * @return Collection<int, EquivalenciaDetalle>
     */
    private function detallesDelPeriodo(int $periodoId): Collection
    {
        return $this->container->make(EscalaComportamiento::class)
            ->delPeriodo($periodoId)
            ?->detalles
            ->keyBy(fn (EquivalenciaDetalle $detalle): int => (int) $detalle->id) ?? collect();
    }

    private function asignaciones(): AsignacionesDocente
    {
        return $this->container->make(AsignacionesDocente::class);
    }
}
