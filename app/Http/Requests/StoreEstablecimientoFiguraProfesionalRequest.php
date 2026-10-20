<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Http\Requests\Concerns\UsesActiveOfertaPeriodo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEstablecimientoFiguraProfesionalRequest extends FormRequest
{
    use UsesActiveOfertaPeriodo;

    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::Admin) === true
            && ($this->user()?->establecimiento?->bachillerato_tecnico ?? false);
    }

    /** @return array<string, list<ValidationRule|string>> */
    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('establecimiento_figuras_profesionales', 'nombre')
                    ->where('establecimiento_modalidad_jornada_id', $this->oferta()?->id),
            ],
        ];
    }
}
