<?php

namespace App\Http\Requests;

use App\Enums\TipoPrograma;
use App\Http\Requests\Concerns\UsesActiveOfertaPeriodo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramaAcademicoRequest extends FormRequest
{
    use UsesActiveOfertaPeriodo;

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::enum(TipoPrograma::class)],
            'nombre' => ['required', 'string', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo.required' => 'Selecciona el tipo de programa.',
            'nombre.required' => 'Escribe el nombre del programa.',
            'nombre.max' => 'El nombre del programa puede tener hasta 150 caracteres.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->oferta() === null || $this->periodo() === null) {
                    $validator->errors()->add('nombre', 'Selecciona la jornada y el periodo activos.');
                }
            },
        ];
    }
}
