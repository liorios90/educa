<?php

namespace App\Http\Requests;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\Role;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateEstablecimientoAulaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasRole(Role::Admin)
            && $user->establecimiento_id !== null;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        $aula = $this->aula();
        $periodo = $this->periodo();

        return [
            'paralelo' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique('establecimiento_aulas', 'paralelo')
                    ->where('establecimiento_periodo_id', $periodo?->id)
                    ->where('establecimiento_grado_id', $aula->establecimiento_grado_id)
                    ->ignore($aula->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'paralelo.required' => 'Indica el paralelo del aula.',
            'paralelo.regex' => 'El paralelo solo puede tener letras y números.',
            'paralelo.max' => 'El paralelo puede tener hasta 10 caracteres.',
            'paralelo.unique' => 'Ya existe un aula con ese paralelo en este grado y periodo.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $aula = $this->aula();
        $oferta = $this->oferta();
        $periodo = $this->periodo();
        $user = $this->user();

        abort_unless(
            $user !== null
            && $oferta !== null
            && $periodo !== null
            && (int) $aula->establecimiento_id === (int) $user->establecimiento_id
            && (int) $aula->establecimiento_modalidad_jornada_id === (int) $oferta->id
            && (int) $aula->establecimiento_periodo_id === (int) $periodo->id,
            404,
        );

        $this->errorBag = 'aula-edit';

        $this->merge([
            'paralelo' => Str::upper(Str::of((string) $this->input('paralelo'))->trim()->toString()),
            'aula_id' => $aula->id,
        ]);
    }

    public function oferta(): ?EstablecimientoModalidadJornada
    {
        return $this->container->make(ActiveOferta::class)->get($this->user());
    }

    public function periodo(): ?EstablecimientoPeriodo
    {
        return $this->container->make(ActivePeriodo::class)->get($this->user());
    }

    private function aula(): EstablecimientoAula
    {
        $aula = $this->route('aula');

        abort_unless($aula instanceof EstablecimientoAula, 404);

        return $aula;
    }
}
