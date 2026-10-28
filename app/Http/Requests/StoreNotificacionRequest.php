<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\DestinatariosNotificacion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreNotificacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'destinatarios' => ['required', 'array', 'min:1'],
            'destinatarios.*' => ['integer'],
            'asunto' => ['required', 'string', 'max:160'],
            'cuerpo' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'destinatarios.required' => 'Selecciona al menos un destinatario.',
            'destinatarios.min' => 'Selecciona al menos un destinatario.',
            'asunto.required' => 'Escribe el asunto.',
            'cuerpo.required' => 'Escribe el mensaje.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('destinatarios')) {
                    return;
                }

                $user = $this->user();

                if (! $user instanceof User) {
                    return;
                }

                $permitidos = collect($this->container->make(DestinatariosNotificacion::class)->idsPermitidos($user));
                $solicitados = collect($this->input('destinatarios', []))
                    ->map(fn (mixed $id): int => (int) $id)
                    ->unique()
                    ->values();

                if ($solicitados->diff($permitidos)->isNotEmpty()) {
                    $validator->errors()->add('destinatarios', 'Selecciona únicamente destinatarios permitidos para tu rol.');
                }
            },
        ];
    }
}
