<?php

namespace App\Http\Requests;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\Role;
use App\Models\Empleado;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEstablecimientoAulaDocentesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasRole(Role::Admin)
            && $user->establecimiento_id !== null;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'docentes' => ['present', 'array'],
            'docentes.*' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'docentes.present' => 'Indica el docente de cada asignatura.',
            'docentes.array' => 'Indica el docente de cada asignatura.',
            'docentes.*.integer' => 'Selecciona un docente que tenga esta asignatura como especialidad.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $aula = $this->aula();
                $aula->loadMissing('establecimientoGrado');
                $malla = EstablecimientoAsignatura::query()
                    ->where('establecimiento_id', $aula->establecimiento_id)
                    ->where('establecimiento_modalidad_jornada_id', $aula->establecimiento_modalidad_jornada_id)
                    ->where('grado_id', $aula->establecimientoGrado?->grado_id)
                    ->get()
                    ->keyBy('id');

                /** @var array<int, int|null> $docentes */
                $docentes = $this->input('docentes', []);

                $activos = Empleado::query()
                    ->with('especialidades')
                    ->where('activo', 1)
                    ->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $aula->establecimiento_id))
                    ->whereHas('persona.user', fn ($query) => $query->role(Role::Docente->value))
                    ->get();

                foreach ($docentes as $mallaId => $empleadoId) {
                    if (! $malla->has((int) $mallaId)) {
                        $validator->errors()->add(
                            'docentes',
                            'La asignatura no pertenece al grado de esta aula.',
                        );

                        return;
                    }

                    if ($empleadoId === null) {
                        continue;
                    }

                    $asignaturaId = (int) $malla->get((int) $mallaId)?->asignatura_id;
                    $especialistas = $activos->filter(
                        fn (Empleado $docente): bool => $docente->especialidades->contains('id', $asignaturaId),
                    );

                    if ($especialistas->isNotEmpty()) {
                        if (! $especialistas->contains('id', (int) $empleadoId)) {
                            $validator->errors()->add(
                                'docentes.'.$mallaId,
                                'Selecciona un docente que tenga esta asignatura como especialidad.',
                            );
                        }

                        continue;
                    }

                    if (! $activos->contains('id', (int) $empleadoId)) {
                        $validator->errors()->add(
                            'docentes.'.$mallaId,
                            'Selecciona un docente activo.',
                        );
                    }
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $aula = $this->aula();
        $oferta = $this->oferta();
        $periodo = $this->periodo();
        $user = $this->user();

        abort_unless(
            $user !== null
            && $oferta !== null
            && $periodo !== null
            && (int) $aula->establecimiento_id === (int) $user->establecimiento_id
            && (int) $aula->establecimiento_modalidad_jornada_id === (int) $oferta->id
            && (int) $aula->establecimiento_periodo_id === (int) $periodo->id,
            404,
        );

        $this->errorBag = 'aula-docentes';

        $docentes = $this->input('docentes', []);
        $normalizados = [];

        if (is_array($docentes)) {
            foreach ($docentes as $mallaId => $empleadoId) {
                $normalizados[(int) $mallaId] = $empleadoId === null || $empleadoId === ''
                    ? null
                    : (int) $empleadoId;
            }
        }

        $this->merge([
            'docentes' => $normalizados,
            'aula_id' => $aula->id,
        ]);
    }

    public function oferta(): ?EstablecimientoModalidadJornada
    {
        return $this->container->make(ActiveOferta::class)->get($this->user());
    }

    public function periodo(): ?EstablecimientoPeriodo
    {
        return $this->container->make(ActivePeriodo::class)->get($this->user());
    }

    private function aula(): EstablecimientoAula
    {
        $aula = $this->route('aula');

        abort_unless($aula instanceof EstablecimientoAula, 404);

        return $aula;
    }
}
