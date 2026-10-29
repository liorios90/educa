<?php

namespace App\Http\Requests;

use App\Auth\DocenteContexto;
use App\Enums\Role;
use App\Enums\TipoCalificacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoTarea;
use App\Models\TipoInsumoDetalle;
use App\Services\AsignacionesDocente;
use App\Services\DecimalesEstablecimiento;
use App\Services\TareasDocente;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CalificarTareaRequest extends FormRequest
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
        $tarea = $this->route('tarea');

        if ($user === null || ! $user->hasRole(Role::Docente) || ! $tarea instanceof EstablecimientoTarea) {
            return false;
        }

        if (! $this->tareas()->puedeGestionar($user, $tarea)) {
            abort(404);
        }

        return true;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
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
                $tarea = $this->route('tarea');

                if ($user === null || ! $tarea instanceof EstablecimientoTarea) {
                    abort(404);
                }

                if ($tarea->estaCalificada()) {
                    $validator->errors()->add(
                        'tipo_insumo_detalle_id',
                        'Esta tarea ya está calificada. Cámbiala desde Editar insumo.',
                    );

                    return;
                }

                $asignaciones = $this->asignaciones();
                $contexto = $asignaciones->contexto(
                    $user,
                    (int) $tarea->establecimiento_aula_id,
                    (int) $tarea->establecimiento_asignatura_id,
                    (int) $tarea->establecimiento_periodo_ciclo_id,
                    (int) $tarea->parcial_numero,
                );

                if ($contexto === null) {
                    abort(404);
                }

                $detalle = $asignaciones->detalleAsignado(
                    (int) $tarea->establecimiento_aula_id,
                    (int) $tarea->establecimiento_asignatura_id,
                    (int) $this->input('tipo_insumo_detalle_id'),
                );

                if ($detalle === null) {
                    abort(404);
                }

                $detalle->loadMissing('tipoInsumo');

                $this->contexto = $contexto;
                $this->detalle = $detalle;
                $this->empleadoId = $this->tareas()->empleadoIdDeAsignacion($user, $contexto);
                $this->validarNotas($validator, $asignaciones, $contexto, $detalle, $tarea);
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

    private function tareas(): TareasDocente
    {
        return $this->container->make(TareasDocente::class);
    }

    private function validarNotas(
        Validator $validator,
        AsignacionesDocente $asignaciones,
        DocenteContexto $contexto,
        TipoInsumoDetalle $detalle,
        EstablecimientoTarea $tarea,
    ): void {
        $payload = $this->input('notas', []);

        if ($payload === null) {
            $payload = [];
        }

        if (! is_array($payload)) {
            $validator->errors()->add('notas', 'Las calificaciones no son válidas.');

            return;
        }

        $permitidos = array_fill_keys(
            $tarea->destinatarios()->pluck('alumno_id')->map(fn (mixed $id): int => (int) $id)->all(),
            true,
        );
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
