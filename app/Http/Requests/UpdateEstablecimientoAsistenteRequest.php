<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Establecimiento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEstablecimientoAsistenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasRole(Role::Admin)
            && $this->establecimiento() !== null;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'gemini_api_key' => [
                Rule::requiredIf(fn (): bool => ! $this->establecimiento()?->tieneCredencialesGemini()),
                'nullable',
                'string',
                'max:500',
            ],
            'gemini_model' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            'gemini_preguntas_limite' => ['required', 'integer', 'min:1', 'max:1000000'],
            'gemini_tokens_limite' => ['required', 'integer', 'min:1', 'max:100000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gemini_api_key.required' => 'Ingresa la clave de Gemini de este instituto.',
            'gemini_api_key.max' => 'La clave de Gemini no puede superar 500 caracteres.',
            'gemini_model.required' => 'Indica el modelo de Gemini de este instituto.',
            'gemini_model.max' => 'El modelo de Gemini no puede superar 100 caracteres.',
            'gemini_model.regex' => 'El modelo solo puede contener letras, números, puntos, guiones y guiones bajos.',
            'gemini_preguntas_limite.required' => 'Indica cuántas preguntas puede hacer el instituto cada mes.',
            'gemini_preguntas_limite.min' => 'El cupo de preguntas debe ser al menos 1.',
            'gemini_preguntas_limite.max' => 'El cupo de preguntas no puede superar 1.000.000.',
            'gemini_tokens_limite.required' => 'Indica cuántos tokens puede gastar el instituto cada mes.',
            'gemini_tokens_limite.min' => 'El cupo de tokens debe ser al menos 1.',
            'gemini_tokens_limite.max' => 'El cupo de tokens no puede superar 100.000.000.',
        ];
    }

    public function establecimiento(): ?Establecimiento
    {
        $establecimientoId = $this->user()?->establecimiento_id;

        if ($establecimientoId === null) {
            return null;
        }

        return Establecimiento::query()->find($establecimientoId);
    }
}
