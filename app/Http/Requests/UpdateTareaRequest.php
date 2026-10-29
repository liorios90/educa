<?php

namespace App\Http\Requests;

use App\Enums\TipoContenidoTarea;
use App\Models\EstablecimientoTarea;
use App\Services\TareasDocente;
use Illuminate\Contracts\Validation\Validator;

class UpdateTareaRequest extends StoreTareaRequest
{
    /**
     * @return list<string>
     */
    protected function reglasArchivo(): array
    {
        $tipo = TipoContenidoTarea::tryFrom((string) $this->input('contenido_tipo'));
        $tarea = $this->tareaActual();
        $tieneArchivo = $tarea?->contenido_path !== null && $tarea->contenido_tipo === $tipo;

        if ($tipo === TipoContenidoTarea::Pdf) {
            return [$tieneArchivo ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:10240'];
        }

        if ($tipo === TipoContenidoTarea::Imagen) {
            return [$tieneArchivo ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
        }

        return ['nullable', 'file'];
    }

    public function authorize(): bool
    {
        $user = $this->user();
        $tarea = $this->tareaActual();

        if ($user === null || ! parent::authorize()) {
            return false;
        }

        if ($tarea === null || ! $this->container->make(TareasDocente::class)->puedeGestionar($user, $tarea)) {
            abort(404);
        }

        return true;
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
                $tarea = $this->tareaActual();

                if ($user === null || $tarea === null) {
                    abort(404);
                }

                $contexto = $this->asignaciones()->contexto(
                    $user,
                    (int) $tarea->establecimiento_aula_id,
                    (int) $tarea->establecimiento_asignatura_id,
                    (int) $tarea->establecimiento_periodo_ciclo_id,
                    (int) $tarea->parcial_numero,
                );

                if ($contexto === null) {
                    abort(404);
                }

                $this->contexto = $contexto;
                $this->validarContenido($validator);
                $this->validarDestinatarios($validator, $this->asignaciones(), $contexto);
            },
        ];
    }

    private function tareaActual(): ?EstablecimientoTarea
    {
        $tarea = $this->route('tarea');

        return $tarea instanceof EstablecimientoTarea ? $tarea : null;
    }
}
