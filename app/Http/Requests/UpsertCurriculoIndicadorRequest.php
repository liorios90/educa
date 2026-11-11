<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Destreza;
use App\Models\Sys_IndicadorEvaluacion;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertCurriculoIndicadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Role::Sistemas, Role::Admin]) ?? false;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        $destreza = $this->route('destreza');
        $indicador = $this->route('indicador');
        $destrezaId = $destreza instanceof Sys_Destreza ? $destreza->id : null;
        $ignoreId = $indicador instanceof Sys_IndicadorEvaluacion ? $indicador->id : null;

        return [
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('sys_indicadores_evaluacion', 'codigo')
                    ->where('destreza_id', $destrezaId)
                    ->ignore($ignoreId),
            ],
            'descripcion' => ['required', 'string', 'max:500'],
            'orden' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'Escribe el código del indicador.',
            'codigo.unique' => 'Ya existe un indicador con ese código en la destreza.',
            'descripcion.required' => 'Escribe el indicador de evaluación.',
            'descripcion.max' => 'El indicador no puede pasar de 500 caracteres.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $subnivel = $this->route('subnivel');

                if (! $subnivel instanceof Sys_Subnivel) {
                    $asignatura = $this->route('asignatura');

                    if ($asignatura instanceof Sys_Asignatura) {
                        $asignatura->loadMissing('area.subnivel');
                        $subnivel = $asignatura->area?->subnivel;
                    }
                }

                if ($subnivel instanceof Sys_Subnivel && ! $subnivel->califica_por_destrezas) {
                    $validator->errors()->add('descripcion', 'Este subnivel no evalúa por destrezas.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $indicador = $this->route('indicador');
        $destreza = $this->route('destreza');

        $this->merge([
            'orden' => $this->filled('orden') ? $this->integer('orden') : 0,
        ]);

        if ($indicador instanceof Sys_IndicadorEvaluacion) {
            $this->errorBag = 'indicador-'.$indicador->id;

            return;
        }

        $this->errorBag = 'indicador-create-'.($destreza instanceof Sys_Destreza ? $destreza->id : 'new');
    }

    protected function getRedirectUrl(): string
    {
        $oferta = $this->route('oferta');

        if ($oferta instanceof EstablecimientoModalidadJornada) {
            $asignatura = $this->route('asignatura');
            $grado = $this->input('grado');

            return route('Admin.asignaturas.edit', [
                'oferta' => $oferta,
                'area' => $asignatura instanceof Sys_Asignatura ? $asignatura->area_id : null,
                'destrezas' => $asignatura instanceof Sys_Asignatura ? $asignatura->id.'-'.$grado : null,
            ]);
        }

        $nivel = $this->route('nivel');
        $subnivel = $this->route('subnivel');
        $area = $this->route('area');

        return route('sistemas.curriculo', array_filter([
            'nivel' => $nivel instanceof Sys_Nivel ? $nivel->id : null,
            'subnivel' => $subnivel instanceof Sys_Subnivel ? $subnivel->id : null,
            'area' => $area?->id,
        ]));
    }
}
