<?php

namespace App\Http\Requests;

use App\Auth\DocenteContexto;
use App\Enums\Role;
use App\Enums\TipoCalificacion;
use App\Models\Alumno;
use App\Models\EstablecimientoAulaDocente;
use App\Models\TipoInsumoDetalle;
use App\Services\AsignacionesDocente;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreInsumoActividadRequest extends FormRequest
{
    private ?DocenteContexto $contexto = null;

    private ?TipoInsumoDetalle $detalle = null;

    private ?int $empleadoId = null;

    /**
     * @var array<int, string>
     */
    private array $notas = [];

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
            'parcial_numero' => ['required', 'integer', 'min:1'],
            'nombre' => ['required', 'string', 'max:255'],
            'tipo_insumo_detalle_id' => ['required', 'integer'],
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
            'parcial_numero.required' => 'Selecciona un parcial.',
            'parcial_numero.min' => 'Selecciona un parcial.',
            'nombre.required' => 'Escribe el nombre de la actividad.',
            'tipo_insumo_detalle_id.required' => 'Selecciona un tipo de insumo.',
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
                $contexto = $asignaciones->contexto(
                    $user,
                    (int) $this->input('establecimiento_aula_id'),
                    (int) $this->input('establecimiento_asignatura_id'),
                    (int) $this->input('establecimiento_periodo_ciclo_id'),
                    (int) $this->input('parcial_numero'),
                );

                if ($contexto === null) {
                    abort(404);
                }

                $detalle = $asignaciones->detalleAsignado(
                    (int) $contexto->aula->id,
                    (int) $contexto->asignatura->id,
                    (int) $this->input('tipo_insumo_detalle_id'),
                );

                if ($detalle === null) {
                    abort(404);
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
                $this->detalle = $detalle;
                $this->empleadoId = (int) $asignacion->empleado_id;
                $this->validarNotas($validator, $asignaciones, $contexto, $detalle);
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

    public function detalle(): TipoInsumoDetalle
    {
        if (! $this->detalle instanceof TipoInsumoDetalle) {
            abort(404);
        }

        return $this->detalle;
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

    private function asignaciones(): AsignacionesDocente
    {
        return $this->container->make(AsignacionesDocente::class);
    }

    private function validarNotas(
        Validator $validator,
        AsignacionesDocente $asignaciones,
        DocenteContexto $contexto,
        TipoInsumoDetalle $detalle,
    ): void {
        $payload = $this->input('notas', []);

        if ($payload === null) {
            $payload = [];
        }

        if (! is_array($payload)) {
            $validator->errors()->add('notas', 'Las calificaciones no son válidas.');

            return;
        }

        $alumnos = $asignaciones->alumnos($contexto);
        $permitidos = array_fill_keys(
            $alumnos->map(fn (Alumno $alumno): int => (int) $alumno->id)->all(),
            true,
        );
        $tipoCalificacion = TipoCalificacion::tryFrom((string) $detalle->tipoInsumo?->tipo_calificacion)
            ?? $asignaciones->tipoCalificacion($contexto);
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
