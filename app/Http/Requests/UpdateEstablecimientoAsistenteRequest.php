<?php

namespace App\Http\Requests;

use App\Enums\AsistenteProveedor;
use App\Enums\Role;
use App\Models\Establecimiento;
use App\Rules\AsistenteBaseUrl;
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

    protected function prepareForValidation(): void
    {
        $url = $this->input('asistente_base_url');

        if (! is_string($url)) {
            return;
        }

        $url = trim($url);

        $this->merge([
            'asistente_base_url' => $url === '' ? null : $url,
        ]);
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        $compatible = $this->string('asistente_proveedor')->toString() === AsistenteProveedor::Compatible->value;

        return [
            'asistente_proveedor' => ['required', Rule::enum(AsistenteProveedor::class)],
            'asistente_base_url' => [
                Rule::requiredIf($compatible),
                'nullable',
                'string',
                'max:255',
                new AsistenteBaseUrl,
            ],
            'gemini_api_key' => [
                Rule::requiredIf(fn (): bool => ! $this->establecimiento()?->tieneCredencialesGemini()),
                'nullable',
                'string',
                'max:500',
            ],
            'gemini_model' => [
                'required',
                'string',
                'max:200',
                $compatible ? 'regex:/^[A-Za-z0-9._\/:-]+$/' : 'regex:/^[A-Za-z0-9._-]+$/',
            ],
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
            'asistente_proveedor.required' => 'Elige el proveedor del asistente.',
            'asistente_proveedor.enum' => 'Elige Gemini o una API compatible con OpenAI.',
            'asistente_base_url.required' => 'Ingresa la URL base del asistente.',
            'asistente_base_url.max' => 'La URL base no puede superar 255 caracteres.',
            'gemini_api_key.required' => 'Ingresa la clave del asistente de este instituto.',
            'gemini_api_key.max' => 'La clave no puede superar 500 caracteres.',
            'gemini_model.required' => 'Indica el modelo del asistente de este instituto.',
            'gemini_model.max' => 'El modelo no puede superar 200 caracteres.',
            'gemini_model.regex' => $this->string('asistente_proveedor')->toString() === AsistenteProveedor::Compatible->value
                ? 'El modelo solo puede contener letras, números, puntos, guiones, guiones bajos, barras y dos puntos.'
                : 'El modelo solo puede contener letras, números, puntos, guiones y guiones bajos.',
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
