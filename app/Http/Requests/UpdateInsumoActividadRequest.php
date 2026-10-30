<?php

namespace App\Http\Requests;

use App\Auth\DocenteContexto;
use App\Enums\Role;
use App\Enums\TipoCalificacion;
use App\Models\Alumno;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoInsumoActividad;
use App\Models\TipoInsumoDetalle;
use App\Services\AsignacionesDocente;
use App\Services\DecimalesEstablecimiento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInsumoActividadRequest extends FormRequest
{
    private ?DocenteContexto $contexto = null;

    private ?EstablecimientoInsumoActividad $actividad = null;

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
            'establecimiento_periodo_ciclo_id' => ['required', 'integer'],
            'parcial_numero' => ['required', 'integer', 'min:1'],
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
            'parcial_numero.required' => 'Selecciona un parcial.',
            'parcial_numero.min' => 'Selecciona un parcial.',
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

                if (! $contexto->permiteIngresarCalificaciones()) {
                    $validator->errors()->add('nombre', DocenteContexto::MENSAJE_PERIODO_CERRADO);

                    return;
                }

                if ($contexto->parcialEstaCerrado()) {
                    $validator->errors()->add('parcial_numero', DocenteContexto::MENSAJE_PARCIAL_CERRADO);

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

                $parametro = $this->route('actividad');
                $actividadId = $parametro instanceof EstablecimientoInsumoActividad
                    ? (int) $parametro->id
                    : (int) $parametro;

                $actividad = $asignaciones->actividadDelContexto($contexto, $actividadId);

                if ($actividad === null) {
                    abort(404);
                }

                $detalle = $actividad->detalle;

                if (! $detalle instanceof TipoInsumoDetalle) {
                    abort(404);
                }

                $this->contexto = $contexto;
                $this->actividad = $actividad;
                $this->alumnoIds = $asignaciones->alumnos($contexto)
                    ->map(fn (Alumno $alumno): int => (int) $alumno->id)
                    ->all();
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

    public function actividad(): EstablecimientoInsumoActividad
    {
        if (! $this->actividad instanceof EstablecimientoInsumoActividad) {
            abort(404);
        }

        return $this->actividad;
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

        $permitidos = array_fill_keys($this->alumnoIds, true);
        $tipoCalificacion = TipoCalificacion::tryFrom((string) $detalle->tipoInsumo?->tipo_calificacion)
            ?? $asignaciones->tipoCalificacion($contexto);
        $valoresPermitidos = array_column($asignaciones->valoresCualitativos($contexto), 'valor');
        $escala = $contexto->asignatura->escala;
        $decimales = $this->container->make(DecimalesEstablecimiento::class);
        $establecimiento = Establecimiento::query()->find($this->user()?->establecimiento_id);
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

            $valor = $decimales->aplicar((float) $texto, $establecimiento);

            if (! $escala->contiene($valor)) {
                $validator->errors()->add('notas.'.$alumnoId, $escala->mensajeRango());

                continue;
            }

            $normalizadas[(int) $alumnoId] = $decimales->formatear($valor, $establecimiento);
        }

        $this->notas = $normalizadas;
    }
}
