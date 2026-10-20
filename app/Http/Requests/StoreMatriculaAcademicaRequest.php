<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\UsesActiveOfertaPeriodo;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoFiguraProfesional;
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
            'figura_profesional_id' => ['nullable', 'integer'],
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

                    return;
                }

                if ($validator->errors()->has('establecimiento_aula_id') || $validator->errors()->has('figura_profesional_id')) {
                    return;
                }

                $aula = EstablecimientoAula::query()
                    ->with('establecimientoGrado.grado.subnivel.nivel')
                    ->where('establecimiento_id', $this->user()?->establecimiento_id)
                    ->where('establecimiento_modalidad_jornada_id', $this->oferta()?->id)
                    ->where('establecimiento_periodo_id', $this->periodo()?->id)
                    ->find($this->integer('establecimiento_aula_id'));

                if ($aula === null) {
                    return;
                }

                $figuraId = $this->integer('figura_profesional_id') ?: null;

                if ($figuraId !== null
                    && ($aula->establecimientoGrado?->grado?->subnivel?->nombre !== 'Bachillerato General Unificado'
                        || $aula->establecimientoGrado?->grado?->subnivel?->nivel?->nombre !== 'Bachillerato')) {
                    $validator->errors()->add(
                        'figura_profesional_id',
                        'La figura profesional corresponde a los cursos de Bachillerato General Unificado.',
                    );

                    return;
                }

                $asignaturasTecnicas = EstablecimientoAsignatura::query()
                    ->where('establecimiento_modalidad_jornada_id', $this->oferta()?->id)
                    ->where('grado_id', $aula->establecimientoGrado?->grado_id)
                    ->where(function ($query): void {
                        $query->whereNotNull('figura_profesional_id')
                            ->orWhereHas('asignaturaPropia', fn ($query) => $query->whereNotNull('figura_profesional_id'));
                    });

                if ($figuraId === null && $asignaturasTecnicas->exists()) {
                    $validator->errors()->add('figura_profesional_id', 'Selecciona la especialidad técnica para este paralelo.');

                    return;
                }

                if ($figuraId !== null) {
                    $figuraValida = EstablecimientoFiguraProfesional::query()
                        ->whereKey($figuraId)
                        ->where('establecimiento_id', $this->user()?->establecimiento_id)
                        ->where('establecimiento_modalidad_jornada_id', $this->oferta()?->id)
                        ->exists();
                    $tieneModulos = (clone $asignaturasTecnicas)
                        ->where(function ($query) use ($figuraId): void {
                            $query->where('figura_profesional_id', $figuraId)
                                ->orWhereHas('asignaturaPropia', fn ($query) => $query->where('figura_profesional_id', $figuraId));
                        })
                        ->exists();

                    if (! $figuraValida || ! $tieneModulos) {
                        $validator->errors()->add('figura_profesional_id', 'La especialidad seleccionada no tiene módulos configurados para el curso de este paralelo.');
                    }
                }
            },
        ];
    }
}
