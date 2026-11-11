<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Destreza;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertCurriculoDestrezaRequest extends FormRequest
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
        $destreza = $this->route('destreza');
        $subnivel = $this->subnivelDelCatalogo();
        $asignaturaId = $asignatura instanceof Sys_Asignatura ? $asignatura->id : null;
        $subnivelId = $subnivel?->id;
        $ignoreId = $destreza instanceof Sys_Destreza ? $destreza->id : null;
        $gradoId = $this->input('grado_id');

        return [
            'grado_id' => [
                'required',
                'integer',
                Rule::exists('sys_grados', 'id')->where('subnivel_id', $subnivelId),
            ],
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('sys_destrezas', 'codigo')
                    ->where('asignatura_id', $asignaturaId)
                    ->where('grado_id', is_numeric($gradoId) ? (int) $gradoId : 0)
                    ->ignore($ignoreId),
            ],
            'descripcion' => ['required', 'string', 'max:500'],
            'orden' => ['required', 'integer', 'min:0', 'max:999'],
            'criterio_evaluacion_id' => [
                'nullable',
                'integer',
                Rule::exists('sys_criterios_evaluacion', 'id')
                    ->where('asignatura_id', $asignaturaId)
                    ->where('grado_id', is_numeric($gradoId) ? (int) $gradoId : 0),
            ],
            'imprescindible' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'grado_id.required' => 'Selecciona el grado de la destreza.',
            'grado_id.exists' => 'El grado no pertenece a este subnivel.',
            'codigo.required' => 'Escribe el código de la destreza.',
            'codigo.unique' => 'Ya existe una destreza con ese código en el grado.',
            'descripcion.required' => 'Escribe la destreza.',
            'descripcion.max' => 'La destreza no puede pasar de 500 caracteres.',
            'criterio_evaluacion_id.exists' => 'El criterio no pertenece a este ámbito y grado.',
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

                if ($subnivel instanceof Sys_Subnivel && ! $subnivel->califica_por_destrezas) {
                    $validator->errors()->add('descripcion', 'Este subnivel no evalúa por destrezas.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $destreza = $this->route('destreza');
        $asignatura = $this->route('asignatura');

        $atributos = [
            'orden' => $this->filled('orden') ? $this->integer('orden') : 0,
            'criterio_evaluacion_id' => $this->filled('criterio_evaluacion_id') ? $this->integer('criterio_evaluacion_id') : null,
        ];

        if ($this->exists('imprescindible')) {
            $atributos['imprescindible'] = $this->boolean('imprescindible');
        }

        $this->merge($atributos);

        if ($destreza instanceof Sys_Destreza) {
            $this->errorBag = 'destreza-'.$destreza->id;

            return;
        }

        $this->errorBag = 'destreza-create-'.($asignatura instanceof Sys_Asignatura ? $asignatura->id : 'new');
    }

    protected function getRedirectUrl(): string
    {
        $oferta = $this->route('oferta');

        if ($oferta instanceof EstablecimientoModalidadJornada) {
            return $this->urlMalla($oferta);
        }

        return $this->urlCurriculo();
    }

    private function urlMalla(EstablecimientoModalidadJornada $oferta): string
    {
        $asignatura = $this->route('asignatura');
        $grado = $this->input('grado_id');

        return route('Admin.asignaturas.edit', [
            'oferta' => $oferta,
            'area' => $asignatura instanceof Sys_Asignatura ? $asignatura->area_id : null,
            'destrezas' => $asignatura instanceof Sys_Asignatura ? $asignatura->id.'-'.$grado : null,
        ]);
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

    private function urlCurriculo(): string
    {
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
