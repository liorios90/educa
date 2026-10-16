<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\UsesActiveOfertaPeriodo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreMatriculaAcademicaRequest extends FormRequest
{
    use UsesActiveOfertaPeriodo;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'alumnos' => collect($this->input('alumnos', []))
                ->filter(fn (mixed $id): bool => is_numeric($id) && (int) $id > 0)
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->all(),
            'fecha' => $this->filled('fecha') ? $this->input('fecha') : now()->toDateString(),
        ]);
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'establecimiento_aula_id' => ['required', 'integer'],
            'fecha' => ['required', 'date'],
            'alumnos' => ['required', 'array', 'min:1'],
            'alumnos.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'establecimiento_aula_id.required' => 'Selecciona el paralelo.',
            'fecha.required' => 'Indica la fecha de matrícula.',
            'fecha.date' => 'La fecha de matrícula no es válida.',
            'alumnos.required' => 'Selecciona al menos un alumno.',
            'alumnos.min' => 'Selecciona al menos un alumno.',
            'alumnos.*.distinct' => 'Cada alumno se selecciona una sola vez.',
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
                    $validator->errors()->add('alumnos', 'Selecciona la jornada y el periodo activos.');
                }
            },
        ];
    }
}
