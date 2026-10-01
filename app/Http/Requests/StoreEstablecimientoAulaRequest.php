<?php

namespace App\Http\Requests;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\Role;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEstablecimientoAulaRequest extends FormRequest
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
        $periodo = $this->periodo();

        return [
            'establecimiento_grado_id' => [
                'required',
                'integer',
                Rule::exists('establecimiento_grados', 'id'),
            ],
            'paralelo' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique('establecimiento_aulas', 'paralelo')
                    ->where('establecimiento_periodo_id', $periodo?->id)
                    ->where('establecimiento_grado_id', $this->integer('establecimiento_grado_id')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'establecimiento_grado_id.required' => 'Selecciona el grado del aula.',
            'establecimiento_grado_id.exists' => 'El grado no pertenece a la modalidad y jornada activas.',
            'paralelo.required' => 'Indica el paralelo del aula.',
            'paralelo.regex' => 'El paralelo solo puede tener letras y números.',
            'paralelo.unique' => 'Ya existe un aula con ese paralelo en este grado y periodo.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->oferta() === null) {
                    $validator->errors()->add(
                        'establecimiento_grado_id',
                        'Selecciona primero la modalidad y la jornada.',
                    );

                    return;
                }

                if ($this->periodo() === null) {
                    $validator->errors()->add(
                        'establecimiento_grado_id',
                        'No existe ningún periodo activo.',
                    );

                    return;
                }

                if ($validator->errors()->has('establecimiento_grado_id')) {
                    return;
                }

                $grado = EstablecimientoGrado::query()->find($this->integer('establecimiento_grado_id'));
                $oferta = $this->oferta();
                $user = $this->user();

                if (
                    $grado === null
                    || $oferta === null
                    || $user === null
                    || (int) $grado->establecimiento_id !== (int) $user->establecimiento_id
                    || (int) $grado->establecimiento_modalidad_jornada_id !== (int) $oferta->id
                ) {
                    $validator->errors()->add(
                        'establecimiento_grado_id',
                        'El grado no pertenece a la modalidad y jornada activas.',
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->errorBag = 'aula-create';

        $this->merge([
            'paralelo' => Str::upper(Str::of((string) $this->input('paralelo'))->trim()->toString()),
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
}
