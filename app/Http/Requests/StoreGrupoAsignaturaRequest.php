<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\UsesActiveOfertaPeriodo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreGrupoAsignaturaRequest extends FormRequest
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
            'establecimiento_asignatura_id' => ['required', 'integer'],
            'nombre' => ['required', 'string', 'max:150'],
            'empleado_id' => ['nullable', 'integer'],
            'alumnos' => ['nullable', 'array'],
            'alumnos.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'establecimiento_asignatura_id.required' => 'Selecciona la asignatura.',
            'nombre.required' => 'Escribe el nombre del grupo.',
            'nombre.max' => 'El nombre del grupo puede tener hasta 150 caracteres.',
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
                    $validator->errors()->add('nombre', 'Selecciona la jornada y el periodo activos.');
                }
            },
        ];
    }
}
