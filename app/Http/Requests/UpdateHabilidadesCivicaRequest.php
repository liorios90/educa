<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Services\AcompanamientoCivica;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHabilidadesCivicaRequest extends FormRequest
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
        $permitidas = [
            ...AcompanamientoCivica::COGNITIVAS,
            ...AcompanamientoCivica::SOCIALES,
            ...AcompanamientoCivica::EMOCIONALES,
        ];

        return [
            'habilidades' => ['required', 'array', 'size:5'],
            'habilidades.*' => ['required', 'string', 'distinct', Rule::in($permitidas)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'habilidades.required' => 'Elige las cinco habilidades de la institución.',
            'habilidades.size' => 'Elige una habilidad cognitiva, tres sociales y una emocional.',
            'habilidades.*.distinct' => 'Cada habilidad se elige una sola vez.',
            'habilidades.*.in' => 'Esa habilidad no forma parte de las que puede elegir la institución.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $codigos = $this->input('habilidades', []);

                if (! is_array($codigos) || $validator->errors()->isNotEmpty()) {
                    return;
                }

                if (! (new AcompanamientoCivica)->seleccionValida($codigos)) {
                    $validator->errors()->add(
                        'habilidades',
                        'Elige una habilidad cognitiva, tres sociales y una emocional. Las cuatro obligatorias ya están incluidas.',
                    );
                }
            },
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
