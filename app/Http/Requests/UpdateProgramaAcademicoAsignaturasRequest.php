<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\UsesActiveOfertaPeriodo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramaAcademicoAsignaturasRequest extends FormRequest
{
    use UsesActiveOfertaPeriodo;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'asignaturas' => collect($this->input('asignaturas', []))
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
            'asignaturas' => ['present', 'array'],
            'asignaturas.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'asignaturas.present' => 'Indica las asignaturas del programa.',
            'asignaturas.*.distinct' => 'Cada asignatura se selecciona una sola vez.',
        ];
    }
}
