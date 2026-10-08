<?php

namespace App\Http\Requests;

use App\Auth\ActiveOferta;
use App\Enums\Role;
use App\Models\Empleado;
use App\Models\EstablecimientoAsignatura;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDocenteEspecialidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! $user->hasRole(Role::Admin) || $user->establecimiento_id === null) {
            return false;
        }

        $empleado = $this->route('empleado');

        if (! $empleado instanceof Empleado) {
            return true;
        }

        $empleado->loadMissing('persona.user');

        abort_unless(
            (int) $empleado->persona?->establecimiento_id === (int) $user->establecimiento_id
            && $empleado->persona?->user?->hasRole(Role::Docente) === true,
            404,
        );

        return true;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'areas' => ['array'],
            'areas.*' => ['integer', 'distinct'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'areas' => collect($this->input('areas', []))
                ->filter(fn (mixed $id): bool => is_numeric($id))
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $permitidas = $this->areasDeLaSesion();

                if ($permitidas === []) {
                    $validator->errors()->add(
                        'areas',
                        'Define la malla de esta jornada antes de registrar la especialidad del docente.',
                    );

                    return;
                }

                $elegidas = array_map(
                    fn (mixed $id): int => (int) $id,
                    (array) $this->input('areas', []),
                );

                if ($elegidas === [] || array_diff($elegidas, $permitidas) !== []) {
                    $validator->errors()->add(
                        'areas',
                        'Marca las áreas de especialización del docente.',
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'areas.*.integer' => 'Marca las áreas de especialización del docente.',
        ];
    }

    /**
     * @return list<int>
     */
    public function asignaturaIds(): array
    {
        /** @var list<int> $areaIds */
        $areaIds = array_map(
            fn (mixed $id): int => (int) $id,
            (array) $this->validated('areas'),
        );

        $user = $this->user();
        $oferta = $this->container->make(ActiveOferta::class)->get($user);

        if ($user?->establecimiento_id === null || $oferta === null || $areaIds === []) {
            return [];
        }

        return EstablecimientoAsignatura::query()
            ->where('establecimiento_id', $user->establecimiento_id)
            ->where('establecimiento_modalidad_jornada_id', $oferta->id)
            ->whereHas('asignatura', fn ($query) => $query->whereIn('area_id', $areaIds))
            ->distinct()
            ->orderBy('asignatura_id')
            ->pluck('asignatura_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function areasDeLaSesion(): array
    {
        $user = $this->user();
        $oferta = $this->container->make(ActiveOferta::class)->get($user);

        if ($user?->establecimiento_id === null || $oferta === null) {
            return [];
        }

        return EstablecimientoAsignatura::query()
            ->with('asignatura')
            ->where('establecimiento_id', $user->establecimiento_id)
            ->where('establecimiento_modalidad_jornada_id', $oferta->id)
            ->orderBy('asignatura_id')
            ->get()
            ->map(fn (EstablecimientoAsignatura $fila): int => (int) $fila->asignatura?->area_id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
