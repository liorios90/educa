<?php

namespace App\Http\Requests;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\Role;
use App\Models\EstablecimientoAula;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreHorarioClaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::Admin) === true
            && $this->user()?->establecimiento_id !== null;
    }

    public function rules(): array
    {
        return [
            'clases' => ['required', 'array', 'min:1', 'max:30'],
            'clases.*.establecimiento_aula_id' => ['required', 'integer', 'min:1'],
            'clases.*.establecimiento_asignatura_id' => ['required', 'integer', 'min:1'],
            'clases.*.dia_semana' => ['required', 'integer', 'between:1,7'],
            'clases.*.hora_inicio' => ['required', 'date_format:H:i'],
            'clases.*.hora_fin' => ['required', 'date_format:H:i'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                foreach ($this->input('clases', []) as $indice => $clase) {
                    if ($clase['hora_fin'] <= $clase['hora_inicio']) {
                        $validator->errors()->add(
                            'clases.'.$indice.'.hora_fin',
                            'La hora de fin debe ser posterior a la hora de inicio.',
                        );
                    }

                    foreach (['hora_inicio', 'hora_fin'] as $campo) {
                        if ((int) substr($clase[$campo], 3, 2) % 15 !== 0) {
                            $validator->errors()->add(
                                'clases.'.$indice.'.'.$campo,
                                'El horario debe comenzar o terminar en un intervalo de 15 minutos.',
                            );
                        }
                    }
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $aula = $this->aula();
        $oferta = $this->container->make(ActiveOferta::class)->get($this->user());
        $periodo = $this->container->make(ActivePeriodo::class)->get($this->user());

        abort_unless(
            $oferta !== null
            && $periodo !== null
            && (int) $aula->establecimiento_id === (int) $this->user()?->establecimiento_id
            && (int) $aula->establecimiento_modalidad_jornada_id === (int) $oferta->id
            && (int) $aula->establecimiento_periodo_id === (int) $periodo->id,
            404,
        );

        $this->errorBag = 'horario';
    }

    private function aula(): EstablecimientoAula
    {
        $aula = $this->route('aula');

        abort_unless($aula instanceof EstablecimientoAula, 404);

        return $aula;
    }
}
