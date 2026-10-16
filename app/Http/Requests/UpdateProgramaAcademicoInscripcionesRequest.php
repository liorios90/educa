<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\UsesActiveOfertaPeriodo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramaAcademicoInscripcionesRequest extends FormRequest
{
    use UsesActiveOfertaPeriodo;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'alumnos' => collect($this->input('alumnos', []))
                ->filter(fn (mixed $id): bool => is_numeric($id) && (int) $id > 0)
                ->map(fn (mixed $id): int => (int) $id)
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'alumnos' => ['present', 'array'],
            'alumnos.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alumnos.present' => 'Indica los alumnos inscritos.',
            'alumnos.*.distinct' => 'Cada alumno se selecciona una sola vez.',
        ];
    }
}
