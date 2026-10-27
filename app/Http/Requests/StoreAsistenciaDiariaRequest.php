<?php

namespace App\Http\Requests;

use App\Auth\ContextoAsistencia;
use App\Enums\EstadoAsistencia;
use App\Enums\ModoAsistencia;
use App\Enums\MotivoJustificacionAsistencia;
use App\Enums\Role;
use App\Services\AsignacionesDocente;
use App\Services\RegistroAsistenciaDiaria;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

class StoreAsistenciaDiariaRequest extends FormRequest
{
    private ?ContextoAsistencia $contexto = null;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasRole(Role::Docente);
    }

    /**
     * @return array<string, list<ValidationRule|RequiredIf|string>>
     */
    public function rules(): array
    {
        return [
            'modo' => ['required', Rule::enum(ModoAsistencia::class)],
            'establecimiento_aula_id' => ['required', 'integer'],
            'establecimiento_asignatura_id' => [$this->exigidoPorAsignatura(), 'nullable', 'integer'],
            'establecimiento_periodo_ciclo_id' => [$this->exigidoPorAsignatura(), 'nullable', 'integer'],
            'parcial_numero' => [$this->exigidoPorAsignatura(), 'nullable', 'integer', 'min:1'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'asistencias' => ['required', 'array'],
            'asistencias.*' => ['array'],
            'asistencias.*.estado' => ['required', Rule::enum(EstadoAsistencia::class)],
            'asistencias.*.justificacion' => ['nullable', Rule::enum(MotivoJustificacionAsistencia::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'modo.required' => 'Elige si la asistencia es del día o de cada asignatura.',
            'modo.enum' => 'Elige si la asistencia es del día o de cada asignatura.',
            'establecimiento_aula_id.required' => 'Selecciona un aula.',
            'establecimiento_asignatura_id.required' => 'Selecciona una materia.',
            'establecimiento_periodo_ciclo_id.required' => 'Selecciona un quimestre o trimestre.',
            'parcial_numero.required' => 'Selecciona un parcial.',
            'parcial_numero.min' => 'Selecciona un parcial.',
            'fecha.required' => 'Indica la fecha de la jornada.',
            'fecha.date_format' => 'Indica una fecha válida.',
            'asistencias.required' => 'Registra la asistencia de cada estudiante.',
            'asistencias.*.estado.required' => 'Indica si el estudiante asistió, faltó o llegó atrasado.',
            'asistencias.*.justificacion.enum' => 'Selecciona un documento de justificación válido.',
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

                $contexto = $this->asignaciones()->contextoAsistencia(
                    $user,
                    (int) $this->input('establecimiento_aula_id'),
                    ModoAsistencia::from((string) $this->input('modo')),
                    $this->entero('establecimiento_asignatura_id'),
                    $this->entero('establecimiento_periodo_ciclo_id'),
                    $this->entero('parcial_numero'),
                );

                if ($contexto === null) {
                    abort(404);
                }

                $this->contexto = $contexto;
                $mensajeFecha = $this->registro()->evaluarFecha($contexto, $this->fecha());

                if ($mensajeFecha !== null) {
                    $validator->errors()->add('fecha', $mensajeFecha);
                }

                $this->validarLista($validator, $contexto);
            },
        ];
    }

    public function contexto(): ContextoAsistencia
    {
        if (! $this->contexto instanceof ContextoAsistencia) {
            abort(404);
        }

        return $this->contexto;
    }

    public function fecha(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', (string) $this->input('fecha'))->startOfDay();
    }

    /**
     * @return array<int, array{estado: string, justificacion: ?string}>
     */
    public function filas(): array
    {
        $filas = [];

        foreach ($this->validated('asistencias') as $alumnoId => $fila) {
            $justificacion = $fila['justificacion'] ?? null;

            $filas[(int) $alumnoId] = [
                'estado' => (string) $fila['estado'],
                'justificacion' => is_string($justificacion) && $justificacion !== '' ? $justificacion : null,
            ];
        }

        return $filas;
    }

    private function validarLista(Validator $validator, ContextoAsistencia $contexto): void
    {
        $alumnos = $contexto->modo->esJornada() || $contexto->asignatura === null
            ? $this->asignaciones()->alumnosDelAula($contexto->aula)
            : $this->asignaciones()->alumnosDe($contexto->aula, $contexto->asignatura);
        $roster = $alumnos
            ->map(fn ($alumno): int => (int) $alumno->id)
            ->sort()
            ->values();

        if ($roster->isEmpty()) {
            $validator->errors()->add('asistencias', 'No hay alumnos matriculados en este aula o asignatura.');

            return;
        }

        $enviados = [];

        foreach ($this->input('asistencias', []) as $alumnoId => $fila) {
            if ((string) (int) $alumnoId !== (string) $alumnoId) {
                $validator->errors()->add('asistencias', 'La lista de estudiantes no coincide con el aula. Vuelve a cargar la asistencia.');

                return;
            }

            $enviados[] = (int) $alumnoId;
            $estado = is_array($fila) ? ($fila['estado'] ?? null) : null;
            $justificacion = is_array($fila) ? ($fila['justificacion'] ?? null) : null;

            if ($estado === EstadoAsistencia::Justificada->value && ($justificacion === null || $justificacion === '')) {
                $validator->errors()->add(
                    'asistencias.'.$alumnoId.'.justificacion',
                    'Indica el documento que justifica la falta.',
                );
            }
        }

        $enviados = collect($enviados)->sort()->values();

        if ($roster->all() !== $enviados->all()) {
            $validator->errors()->add('asistencias', 'La lista de estudiantes no coincide con el aula. Vuelve a cargar la asistencia.');
        }
    }

    private function exigidoPorAsignatura(): RequiredIf
    {
        return Rule::requiredIf(fn (): bool => $this->input('modo') !== ModoAsistencia::Jornada->value);
    }

    private function entero(string $campo): ?int
    {
        $valor = $this->input($campo);

        return is_numeric($valor) ? (int) $valor : null;
    }

    private function asignaciones(): AsignacionesDocente
    {
        return $this->container->make(AsignacionesDocente::class);
    }

    private function registro(): RegistroAsistenciaDiaria
    {
        return $this->container->make(RegistroAsistenciaDiaria::class);
    }
}
