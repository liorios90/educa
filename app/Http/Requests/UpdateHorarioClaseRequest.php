<?php

namespace App\Http\Requests;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\Role;
use App\Models\EstablecimientoAula;
use App\Models\HorarioClase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateHorarioClaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $aula = $this->route('aula');
        $horario = $this->route('horario');

        return $user?->hasRole(Role::Admin) === true
            && $user->establecimiento_id !== null
            && $aula instanceof EstablecimientoAula
            && $horario instanceof HorarioClase
            && (int) $aula->establecimiento_id === (int) $user->establecimiento_id
            && (int) $horario->establecimiento_aula_id === (int) $aula->id;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'dia_semana' => ['required', 'integer', 'between:1,7'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->string('hora_fin')->value() <= $this->string('hora_inicio')->value()) {
                    $validator->errors()->add('hora_fin', 'La hora de fin debe ser posterior a la hora de inicio.');
                }

                foreach (['hora_inicio', 'hora_fin'] as $campo) {
                    if ((int) substr($this->string($campo)->value(), 3, 2) % 15 !== 0) {
                        $validator->errors()->add($campo, 'El horario debe comenzar o terminar en un intervalo de 15 minutos.');
                    }
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $aula = $this->route('aula');
        $horario = $this->route('horario');
        $oferta = $this->container->make(ActiveOferta::class)->get($user);
        $periodo = $this->container->make(ActivePeriodo::class)->get($user);

        abort_unless(
            $user !== null
            && $aula instanceof EstablecimientoAula
            && $horario instanceof HorarioClase
            && $oferta !== null
            && $periodo !== null
            && (int) $aula->establecimiento_id === (int) $user->establecimiento_id
            && (int) $aula->establecimiento_modalidad_jornada_id === (int) $oferta->id
            && (int) $aula->establecimiento_periodo_id === (int) $periodo->id
            && (int) $horario->establecimiento_aula_id === (int) $aula->id,
            404,
        );

        $this->merge($this->input('horarios.'.$horario->id, []));
        $this->errorBag = 'horario';
    }
}
