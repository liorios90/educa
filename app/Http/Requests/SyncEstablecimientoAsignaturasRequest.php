<?php

namespace App\Http\Requests;

use App\Enums\ModoLibretaArea;
use App\Enums\Role;
use App\Enums\TipoCalificacion;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Grado;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncEstablecimientoAsignaturasRequest extends FormRequest
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
            'asignaturas' => ['nullable', 'array'],
            'asignaturas.*' => ['array'],
            'asignaturas.*.grados' => ['nullable', 'array'],
            'asignaturas.*.grados.*' => ['integer', Rule::exists('sys_grados', 'id')],
            'asignaturas.*.horas' => ['nullable', 'array'],
            'asignaturas.*.horas.*' => ['nullable', 'integer', 'min:0', 'max:40'],
            'asignaturas.*.libreta' => ['nullable', 'array'],
            'asignaturas.*.libreta.*' => ['nullable', 'boolean'],
            'asignaturas.*.tipo_calificacion' => ['nullable', 'array'],
            'asignaturas.*.tipo_calificacion.*' => ['nullable', Rule::enum(TipoCalificacion::class)],
            'areas' => ['nullable', 'array'],
            'areas.*' => ['array'],
            'areas.*.grados' => ['nullable', 'array'],
            'areas.*.grados.*' => ['array'],
            'areas.*.grados.*.modo_libreta' => ['nullable', Rule::enum(ModoLibretaArea::class)],
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

                $oferta = $this->oferta();
                $gradoIdsOferta = $oferta->grados()->pluck('sys_grados.id')->map(intval(...))->all();

                foreach ($this->filasCandidatas() as $fila) {
                    if (! in_array($fila['grado_id'], $gradoIdsOferta, true)) {
                        $validator->errors()->add(
                            'asignaturas',
                            'Solo puedes asignar materias a los grados que esta jornada ya ofertó en Estructura.',
                        );

                        return;
                    }

                    $asignatura = Sys_Asignatura::query()->with('area.subnivel')->find($fila['asignatura_id']);
                    $grado = Sys_Grado::query()->find($fila['grado_id']);

                    if ($asignatura === null || $grado === null) {
                        $validator->errors()->add('asignaturas', 'La asignatura o el grado no existen en el catálogo.');

                        return;
                    }

                    if ((int) $asignatura->area?->subnivel_id !== (int) $grado->subnivel_id) {
                        $validator->errors()->add(
                            'asignaturas',
                            'Cada asignatura debe pertenecer al mismo subnivel del grado seleccionado.',
                        );

                        return;
                    }
                }

                foreach ($this->modosAreaCandidatos() as $candidato) {
                    if (! in_array($candidato['grado_id'], $gradoIdsOferta, true)) {
                        $validator->errors()->add(
                            'areas',
                            'Solo puedes configurar la libreta de los grados que esta jornada ya ofertó en Estructura.',
                        );

                        return;
                    }

                    $area = Sys_Area::query()->find($candidato['area_id']);
                    $grado = Sys_Grado::query()->find($candidato['grado_id']);

                    if ($area === null || $grado === null) {
                        $validator->errors()->add('areas', 'El área o el grado no existen en el catálogo.');

                        return;
                    }

                    if ((int) $area->subnivel_id !== (int) $grado->subnivel_id) {
                        $validator->errors()->add(
                            'areas',
                            'El área debe pertenecer al mismo subnivel del grado seleccionado.',
                        );

                        return;
                    }
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
            'asignaturas.*.grados.*.exists' => 'El grado seleccionado no existe en el catálogo de sistemas.',
            'asignaturas.*.horas.*.max' => 'Las horas semanales no pueden ser más de 40.',
            'asignaturas.*.tipo_calificacion.*.Illuminate\Validation\Rules\Enum' => 'La forma de calificar no es válida.',
            'areas.*.grados.*.modo_libreta.Illuminate\Validation\Rules\Enum' => 'La forma de mostrar el área en la libreta no es válida.',
        ];
    }

    /**
     * @return list<array{
     *     asignatura_id: int,
     *     grado_id: int,
     *     aparece_en_libreta: bool,
     *     horas_semanales: int|null,
     *     tipo_calificacion: TipoCalificacion
     * }>
     */
    public function filas(): array
    {
        $filas = [];
        $asignaturas = Sys_Asignatura::query()
            ->with('area.subnivel')
            ->whereIn('id', array_column($this->filasCandidatas(), 'asignatura_id'))
            ->get()
            ->keyBy('id');
        $grados = Sys_Grado::query()
            ->with('subnivel')
            ->whereIn('id', array_column($this->filasCandidatas(), 'grado_id'))
            ->get()
            ->keyBy('id');

        foreach ($this->filasCandidatas() as $fila) {
            $asignatura = $asignaturas->get($fila['asignatura_id']);
            $grado = $grados->get($fila['grado_id']);

            if ($asignatura === null || $grado === null) {
                continue;
            }

            $filas[] = [
                'asignatura_id' => $fila['asignatura_id'],
                'grado_id' => $fila['grado_id'],
                'aparece_en_libreta' => $fila['aparece_en_libreta'],
                'horas_semanales' => $fila['horas_semanales'],
                'tipo_calificacion' => $this->tipoCalificacion($asignatura, $grado, $fila['tipo_calificacion']),
            ];
        }

        return $filas;
    }

    /**
     * @return list<array{area_id: int, grado_id: int, modo_libreta: ModoLibretaArea}>
     */
    public function modosArea(): array
    {
        $filas = $this->filas();
        $areaIds = Sys_Asignatura::query()
            ->whereIn('id', array_column($filas, 'asignatura_id'))
            ->pluck('area_id', 'id');
        $conteo = [];

        foreach ($filas as $fila) {
            $areaId = (int) ($areaIds[$fila['asignatura_id']] ?? 0);

            if ($areaId < 1) {
                continue;
            }

            $conteo[$areaId][$fila['grado_id']] = ($conteo[$areaId][$fila['grado_id']] ?? 0) + 1;
        }

        $posted = $this->modosAreaCandidatosIndexados();
        $modos = [];

        foreach ($conteo as $areaId => $grados) {
            foreach ($grados as $gradoId => $cantidad) {
                if ($cantidad < 2) {
                    continue;
                }

                $valor = $posted[$areaId][$gradoId] ?? ModoLibretaArea::Asignaturas->value;
                $modo = ModoLibretaArea::tryFrom($valor) ?? ModoLibretaArea::Asignaturas;

                $modos[] = [
                    'area_id' => $areaId,
                    'grado_id' => $gradoId,
                    'modo_libreta' => $modo,
                ];
            }
        }

        return $modos;
    }

    public function oferta(): EstablecimientoModalidadJornada
    {
        $oferta = $this->route('oferta');

        abort_unless($oferta instanceof EstablecimientoModalidadJornada, 404);

        return $oferta;
    }

    /**
     * @return list<array{asignatura_id: int, grado_id: int, aparece_en_libreta: bool, horas_semanales: int|null, tipo_calificacion: string|null}>
     */
    private function filasCandidatas(): array
    {
        /** @var array<int|string, mixed> $raw */
        $raw = $this->input('asignaturas', []);
        $filas = [];

        foreach ($raw as $asignaturaId => $payload) {
            if (! is_array($payload)) {
                continue;
            }

            $gradoIds = array_values(array_unique(array_map(intval(...), $payload['grados'] ?? [])));

            foreach ($gradoIds as $gradoId) {
                if ($gradoId < 1) {
                    continue;
                }

                $horas = $payload['horas'][$gradoId] ?? $payload['horas'][(string) $gradoId] ?? null;
                $tipo = $payload['tipo_calificacion'][$gradoId] ?? $payload['tipo_calificacion'][(string) $gradoId] ?? null;
                $libreta = $payload['libreta'][$gradoId] ?? $payload['libreta'][(string) $gradoId] ?? true;

                $filas[] = [
                    'asignatura_id' => (int) $asignaturaId,
                    'grado_id' => $gradoId,
                    'aparece_en_libreta' => filter_var($libreta, FILTER_VALIDATE_BOOLEAN),
                    'horas_semanales' => is_numeric($horas) ? (int) $horas : null,
                    'tipo_calificacion' => is_string($tipo) && $tipo !== '' ? $tipo : null,
                ];
            }
        }

        return $filas;
    }

    /**
     * @return list<array{area_id: int, grado_id: int, modo_libreta: string}>
     */
    private function modosAreaCandidatos(): array
    {
        $modos = [];

        foreach ($this->modosAreaCandidatosIndexados() as $areaId => $grados) {
            foreach ($grados as $gradoId => $modo) {
                $modos[] = [
                    'area_id' => $areaId,
                    'grado_id' => $gradoId,
                    'modo_libreta' => $modo,
                ];
            }
        }

        return $modos;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function modosAreaCandidatosIndexados(): array
    {
        /** @var array<int|string, mixed> $raw */
        $raw = $this->input('areas', []);
        $modos = [];

        foreach ($raw as $areaId => $payload) {
            if (! is_array($payload)) {
                continue;
            }

            $areaId = (int) $areaId;

            if ($areaId < 1) {
                continue;
            }

            foreach ($payload['grados'] ?? [] as $gradoId => $gradoPayload) {
                $gradoId = (int) $gradoId;
                $valor = is_array($gradoPayload)
                    ? ($gradoPayload['modo_libreta'] ?? null)
                    : null;

                if ($gradoId < 1 || ! is_string($valor) || $valor === '') {
                    continue;
                }

                $modos[$areaId][$gradoId] = $valor;
            }
        }

        return $modos;
    }

    private function tipoCalificacion(Sys_Asignatura $asignatura, Sys_Grado $grado, ?string $posted): TipoCalificacion
    {
        if (is_string($posted) && TipoCalificacion::tryFrom($posted) instanceof TipoCalificacion) {
            return TipoCalificacion::from($posted);
        }

        return $grado->subnivel?->tipo_calificacion
            ?? $asignatura->area?->subnivel?->tipo_calificacion
            ?? TipoCalificacion::Calificacion;
    }
}
