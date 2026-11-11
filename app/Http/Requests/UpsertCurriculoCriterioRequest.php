<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\Sys_Asignatura;
use App\Models\Sys_CriterioEvaluacion;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertCurriculoCriterioRequest extends FormRequest
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
        $asignatura = $this->route('asignatura');
        $criterio = $this->route('criterio');
        $subnivel = $this->subnivelDelCatalogo();
        $asignaturaId = $asignatura instanceof Sys_Asignatura ? $asignatura->id : null;
        $ignoreId = $criterio instanceof Sys_CriterioEvaluacion ? $criterio->id : null;
        $gradoId = $this->input('grado_id');

        return [
            'grado_id' => [
                'required',
                'integer',
                Rule::exists('sys_grados', 'id')->where('subnivel_id', $subnivel?->id),
            ],
            'codigo' => [
                'required',
                'string',
                'max:32',
                Rule::unique('sys_criterios_evaluacion', 'codigo')
                    ->where('asignatura_id', $asignaturaId)
                    ->where('grado_id', is_numeric($gradoId) ? (int) $gradoId : 0)
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
            'grado_id.required' => 'Selecciona el grado del criterio.',
            'grado_id.exists' => 'El grado no pertenece a este subnivel.',
            'codigo.required' => 'Escribe el código del criterio.',
            'codigo.unique' => 'Ya existe un criterio con ese código en el grado.',
            'descripcion.required' => 'Escribe el criterio de evaluación.',
            'descripcion.max' => 'El criterio no puede pasar de 500 caracteres.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $subnivel = $this->subnivelDelCatalogo();

                if ($subnivel instanceof Sys_Subnivel && ! $subnivel->usa_ambitos) {
                    $validator->errors()->add('descripcion', 'Los criterios de evaluación se usan en Inicial y Preparatoria.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $criterio = $this->route('criterio');
        $asignatura = $this->route('asignatura');

        $this->merge([
            'orden' => $this->filled('orden') ? $this->integer('orden') : 0,
        ]);

        $this->errorBag = $criterio instanceof Sys_CriterioEvaluacion
            ? 'criterio-'.$criterio->id
            : 'criterio-create-'.($asignatura instanceof Sys_Asignatura ? $asignatura->id : 'new');
    }

    protected function getRedirectUrl(): string
    {
        $oferta = $this->route('oferta');

        if ($oferta instanceof EstablecimientoModalidadJornada) {
            $asignatura = $this->route('asignatura');
            $grado = $this->input('grado_id');

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

    private function subnivelDelCatalogo(): ?Sys_Subnivel
    {
        $subnivel = $this->route('subnivel');

        if ($subnivel instanceof Sys_Subnivel) {
            return $subnivel;
        }

        $asignatura = $this->route('asignatura');

        if (! $asignatura instanceof Sys_Asignatura) {
            return null;
        }

        $asignatura->loadMissing('area.subnivel');

        return $asignatura->area?->subnivel;
    }
}
