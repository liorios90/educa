<?php

namespace App\Http\Requests;

use App\Auth\DocenteContexto;
use App\Enums\Role;
use App\Enums\TipoCalificacion;
use App\Enums\TipoCierreEvaluacion;
use App\Models\Alumno;
use App\Models\EstablecimientoAulaDocente;
use App\Services\AsignacionesDocente;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreCierreActividadRequest extends FormRequest
{
    private ?DocenteContexto $contexto = null;

    private ?int $empleadoId = null;

    /**
     * @var array<int, string>
     */
    private array $notas = [];

    /**
     * @var list<int>
     */
    private array $alumnoIds = [];

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
            'establecimiento_periodo_ciclo_id' => $this->tipo()->requiereCiclo()
                ? ['required', 'integer']
                : ['nullable', 'integer'],
            'nombre' => ['required', 'string', 'max:255'],
            'notas' => ['nullable', 'array'],
            'notas.*' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'establecimiento_aula_id.required' => 'Selecciona un aula.',
            'establecimiento_asignatura_id.required' => 'Selecciona una materia.',
            'establecimiento_periodo_ciclo_id.required' => 'Selecciona un quimestre o trimestre.',
            'nombre.required' => 'Escribe el nombre de la actividad.',
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

                $user->loadMissing('persona.empleados');

                $asignaciones = $this->asignaciones();
                $tipo = $this->tipo();
                $contexto = $asignaciones->contextoCierre(
                    $user,
                    (int) $this->input('establecimiento_aula_id'),
                    (int) $this->input('establecimiento_asignatura_id'),
                    $tipo,
                    $this->filled('establecimiento_periodo_ciclo_id')
                        ? (int) $this->input('establecimiento_periodo_ciclo_id')
                        : null,
                );

                if ($contexto === null) {
                    abort(404);
                }

                if (! $asignaciones->cicloTieneCierre($contexto, $tipo)) {
                    $validator->errors()->add(
                        'nombre',
                        $tipo->mensajeSinConfiguracion(),
                    );

                    return;
                }

                $asignacion = EstablecimientoAulaDocente::query()
                    ->where('establecimiento_aula_id', $contexto->aula->id)
                    ->where('establecimiento_asignatura_id', $contexto->asignatura->id)
                    ->whereIn('empleado_id', $user->persona?->empleados->pluck('id') ?? [])
                    ->first();

                if ($asignacion === null) {
                    abort(404);
                }

                $this->contexto = $contexto;
                $this->empleadoId = (int) $asignacion->empleado_id;
                $this->alumnoIds = $asignaciones->alumnos($contexto)
                    ->map(fn (Alumno $alumno): int => (int) $alumno->id)
                    ->all();
                $this->validarNotas($validator, $asignaciones, $contexto);
            },
        ];
    }

    public function contexto(): DocenteContexto
    {
        if (! $this->contexto instanceof DocenteContexto) {
            abort(404);
        }

        return $this->contexto;
    }

    public function tipo(): TipoCierreEvaluacion
    {
        return TipoCierreEvaluacion::fromRuta((string) $this->route('tipo'));
    }

    public function empleadoId(): int
    {
        if ($this->empleadoId === null) {
            abort(404);
        }

        return $this->empleadoId;
    }

    public function nombre(): string
    {
        return (string) $this->validated('nombre');
    }

    /**
     * @return array<int, string>
     */
    public function notas(): array
    {
        return $this->notas;
    }

    /**
     * @return list<int>
     */
    public function alumnoIds(): array
    {
        return $this->alumnoIds;
    }

    private function asignaciones(): AsignacionesDocente
    {
        return $this->container->make(AsignacionesDocente::class);
    }

    private function validarNotas(
        Validator $validator,
        AsignacionesDocente $asignaciones,
        DocenteContexto $contexto,
    ): void {
        $payload = $this->input('notas', []);

        if ($payload === null) {
            $payload = [];
        }

        if (! is_array($payload)) {
            $validator->errors()->add('notas', 'Las calificaciones no son válidas.');

            return;
        }

        $permitidos = array_fill_keys($this->alumnoIds, true);
        $tipoCalificacion = $asignaciones->tipoCalificacion($contexto);
        $valoresPermitidos = array_column($asignaciones->valoresCualitativos($contexto), 'valor');
        $escala = $contexto->asignatura->escala;
        $normalizadas = [];

        foreach ($payload as $alumnoId => $nota) {
            if (! isset($permitidos[(int) $alumnoId])) {
                abort(404);
            }

            if (! is_scalar($nota) && $nota !== null) {
                $validator->errors()->add('notas.'.$alumnoId, 'La nota no es válida.');

                continue;
            }

            $texto = trim((string) $nota);

            if ($texto === '') {
                continue;
            }

            if ($tipoCalificacion === TipoCalificacion::Cualitativa) {
                if (! in_array($texto, $valoresPermitidos, true)) {
                    $validator->errors()->add('notas.'.$alumnoId, 'Selecciona un valor de la escala cualitativa.');

                    continue;
                }

                $normalizadas[(int) $alumnoId] = $texto;

                continue;
            }

            if (! is_numeric($texto)) {
                $validator->errors()->add('notas.'.$alumnoId, 'La nota debe ser un número.');

                continue;
            }

            $valor = round((float) $texto, 2);

            if (! $escala->contiene($valor)) {
                $validator->errors()->add('notas.'.$alumnoId, $escala->mensajeRango());

                continue;
            }

            $normalizadas[(int) $alumnoId] = number_format($valor, 2, '.', '');
        }

        $this->notas = $normalizadas;
    }
}
