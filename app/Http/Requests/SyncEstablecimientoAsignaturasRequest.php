<?php

namespace App\Http\Requests;

use App\Enums\ModoLibretaArea;
use App\Enums\Role;
use App\Enums\TipoAsignatura;
use App\Enums\TipoCalificacion;
use App\Models\EstablecimientoAsignaturaPropia;
use App\Models\EstablecimientoFiguraProfesional;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Grado;
use App\Models\Sys_Subnivel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class SyncEstablecimientoAsignaturasRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasRole(Role::Admin)
            && $user->establecimiento_id !== null
            && ($user->establecimiento?->bachillerato_tecnico || ! $this->contieneConfiguracionTecnica());
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
            'asignaturas.*.figura_profesional_id' => ['nullable', 'array'],
            'asignaturas.*.figura_profesional_id.*' => ['nullable', 'integer'],
            'asignaturas.*.libreta' => ['nullable', 'array'],
            'asignaturas.*.libreta.*' => ['nullable', 'boolean'],
            'asignaturas.*.tipo_calificacion' => ['nullable', 'array'],
            'asignaturas.*.tipo_calificacion.*' => ['nullable', Rule::enum(TipoCalificacion::class)],
            'asignaturas.*.califica_por_destrezas' => ['nullable', 'array'],
            'asignaturas.*.califica_por_destrezas.*' => ['nullable', 'boolean'],
            'asignaturas.*.usa_tabla_equivalencias' => ['nullable', 'array'],
            'asignaturas.*.usa_tabla_equivalencias.*' => ['nullable', 'boolean'],
            'asignaturas.*.porcentaje' => ['nullable', 'array'],
            'asignaturas.*.porcentaje.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'areas' => ['nullable', 'array'],
            'areas.*' => ['array'],
            'areas.*.grados' => ['nullable', 'array'],
            'areas.*.grados.*' => ['array'],
            'areas.*.grados.*.modo_libreta' => ['nullable', Rule::enum(ModoLibretaArea::class)],
            'areas.*.grados.*.promedio_porcentual' => ['nullable', 'boolean'],
            'propias' => ['nullable', 'array'],
            'propias.*' => ['array'],
            'propias.*.nombre' => ['required', 'string', 'max:150'],
            'propias.*.area_id' => ['required', 'integer', Rule::exists('sys_areas', 'id')],
            'propias.*.tipo' => ['required', Rule::enum(TipoAsignatura::class)],
            'propias.*.figura_profesional_id' => ['nullable', 'integer', Rule::exists('establecimiento_figuras_profesionales', 'id')],
            'propias.*.eliminar' => ['nullable', 'boolean'],
            'propias.*.grados' => ['nullable', 'array'],
            'propias.*.grados.*' => ['integer', Rule::exists('sys_grados', 'id')],
            'propias.*.libreta' => ['nullable', 'array'],
            'propias.*.libreta.*' => ['nullable', 'boolean'],
            'propias.*.tipo_calificacion' => ['nullable', 'array'],
            'propias.*.tipo_calificacion.*' => ['nullable', Rule::enum(TipoCalificacion::class)],
            'propias.*.califica_por_destrezas' => ['nullable', 'array'],
            'propias.*.califica_por_destrezas.*' => ['nullable', 'boolean'],
            'propias.*.usa_tabla_equivalencias' => ['nullable', 'array'],
            'propias.*.usa_tabla_equivalencias.*' => ['nullable', 'boolean'],
            'propias.*.porcentaje' => ['nullable', 'array'],
            'propias.*.porcentaje.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
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
                $gradosOferta = $oferta->grados()->get(['sys_grados.id', 'sys_grados.subnivel_id']);
                $gradoIdsOferta = $gradosOferta->pluck('id')->map(intval(...))->all();
                $subnivelPorGrado = $gradosOferta->mapWithKeys(
                    fn ($grado): array => [(int) $grado->id => (int) $grado->subnivel_id],
                );

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

                    if ($asignatura->area?->es_tecnica) {
                        $this->validarAsignaturaTecnica($validator, $fila, $asignatura, $grado);

                        if ($validator->errors()->isNotEmpty()) {
                            return;
                        }
                    } elseif ($fila['figura_profesional_id'] !== null) {
                        $validator->errors()->add(
                            'asignaturas.'.$fila['asignatura_id'].'.figura_profesional_id.'.$fila['grado_id'],
                            'Solo puedes vincular asignaturas de áreas técnicas a una figura profesional.',
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

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->validarAsignaturasObligatorias($validator, $gradoIdsOferta);

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->validarPropias($validator, $gradoIdsOferta, $subnivelPorGrado);

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->validarPorcentajes($validator);
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
            'asignaturas.*.tipo_calificacion.*.Illuminate\Validation\Rules\Enum' => 'La escala de calificación no es válida.',
            'asignaturas.*.porcentaje.*.min' => 'El porcentaje del área no puede ser menor que 0.',
            'asignaturas.*.porcentaje.*.max' => 'El porcentaje del área no puede ser mayor que 100.',
            'areas.*.grados.*.modo_libreta.Illuminate\Validation\Rules\Enum' => 'La forma de mostrar el área en la libreta no es válida.',
            'propias.*.nombre.required' => 'Indica el nombre de la asignatura.',
            'propias.*.nombre.max' => 'El nombre de la asignatura no puede tener más de 150 caracteres.',
            'propias.*.tipo.Illuminate\Validation\Rules\Enum' => 'El tipo de asignatura no es válido.',
            'propias.*.porcentaje.*.min' => 'El porcentaje del área no puede ser menor que 0.',
            'propias.*.porcentaje.*.max' => 'El porcentaje del área no puede ser mayor que 100.',
        ];
    }

    /**
     * @return list<array{
     *     clave: string,
     *     id: int|null,
     *     nombre: string,
     *     area_id: int,
     *     tipo_asignatura: TipoAsignatura,
     *     figura_profesional_id: int|null,
     *     eliminar: bool
     * }>
     */
    public function definicionesPropias(): array
    {
        return array_map(function (array $definicion): array {
            return [
                'clave' => $definicion['clave'],
                'id' => $definicion['id'],
                'nombre' => $definicion['nombre'],
                'area_id' => $definicion['area_id'],
                'tipo_asignatura' => TipoAsignatura::tryFrom((string) $definicion['tipo']) ?? TipoAsignatura::Optativa,
                'figura_profesional_id' => $definicion['figura_profesional_id'],
                'eliminar' => $definicion['eliminar'],
            ];
        }, array_values(array_filter(
            $this->definicionesCandidatas(),
            fn (array $definicion): bool => $definicion['eliminar'] || ($definicion['nombre'] !== '' && $definicion['tipo'] !== null),
        )));
    }

    /**
     * @return list<array{
     *     asignatura_id: int|null,
     *     asignatura_propia_clave: string|null,
     *     figura_profesional_id: int|null,
     *     area_id: int,
     *     grado_id: int,
     *     tipo_asignatura: TipoAsignatura,
     *     aparece_en_libreta: bool,
     *     horas_catalogo: int|null,
     *     tipo_calificacion: TipoCalificacion,
     *     califica_por_destrezas: bool,
     *     usa_tabla_equivalencias: bool,
     *     porcentaje: string|null,
     *     origen: string
     * }>
     */
    public function filas(): array
    {
        $candidatas = array_merge($this->filasCatalogoPreparadas(), $this->filasPropiasPreparadas());
        $conteo = [];

        foreach ($candidatas as $candidata) {
            $conteo[$candidata['area_id']][$candidata['grado_id']] = ($conteo[$candidata['area_id']][$candidata['grado_id']] ?? 0) + 1;
        }

        $filas = [];

        foreach ($candidatas as $fila) {
            $filas[] = [
                'asignatura_id' => $fila['asignatura_id'],
                'asignatura_propia_clave' => $fila['asignatura_propia_clave'],
                'figura_profesional_id' => $fila['figura_profesional_id'],
                'area_id' => $fila['area_id'],
                'grado_id' => $fila['grado_id'],
                'tipo_asignatura' => $fila['tipo_asignatura'],
                'aparece_en_libreta' => $fila['aparece_en_libreta'],
                'horas_catalogo' => $fila['horas_catalogo'],
                'tipo_calificacion' => $fila['tipo_calificacion'],
                'califica_por_destrezas' => $fila['califica_por_destrezas'],
                'usa_tabla_equivalencias' => $fila['usa_tabla_equivalencias'],
                'porcentaje' => $this->porcentajeDeFila(
                    $fila['tipo_calificacion'],
                    $fila['area_id'],
                    $fila['grado_id'],
                    $conteo[$fila['area_id']][$fila['grado_id']] ?? 0,
                    $fila['porcentaje'],
                ),
                'origen' => $fila['origen'],
            ];
        }

        return $filas;
    }

    /**
     * @return list<array{area_id: int, grado_id: int, modo_libreta: ModoLibretaArea, promedio_porcentual: bool}>
     */
    public function modosArea(): array
    {
        $filas = $this->filas();
        $conteo = [];

        foreach ($filas as $fila) {
            $areaId = (int) $fila['area_id'];

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
                    'promedio_porcentual' => $modo->promediaElArea()
                        && $this->promedioPorcentualPosteado($areaId, $gradoId),
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
     * @return list<array{
     *     asignatura_id: int,
     *     asignatura_propia_clave: null,
     *     area_id: int,
     *     grado_id: int,
     *     tipo_asignatura: TipoAsignatura,
     *     aparece_en_libreta: bool,
     *     horas_catalogo: int|null,
     *     tipo_calificacion: TipoCalificacion,
     *     califica_por_destrezas: bool,
     *     usa_tabla_equivalencias: bool,
     *     porcentaje: float|null,
     *     origen: string
     * }>
     */
    private function filasCatalogoPreparadas(): array
    {
        $candidatas = $this->filasCandidatas();
        $asignaturas = Sys_Asignatura::query()
            ->with(['area.subnivel', 'gradosCurriculares'])
            ->whereIn('id', array_column($candidatas, 'asignatura_id'))
            ->get()
            ->keyBy('id');
        $grados = Sys_Grado::query()
            ->with('subnivel')
            ->whereIn('id', array_column($candidatas, 'grado_id'))
            ->get()
            ->keyBy('id');
        $filas = [];

        foreach ($candidatas as $fila) {
            $asignatura = $asignaturas->get($fila['asignatura_id']);
            $grado = $grados->get($fila['grado_id']);

            if ($asignatura === null || $grado === null) {
                continue;
            }

            $calificacion = $this->calificacionDeFila($asignatura, $grado, $fila);
            $gradoCurricular = $asignatura->gradosCurriculares->firstWhere('id', $fila['grado_id']);
            $tipoAsignatura = $gradoCurricular === null
                ? TipoAsignatura::TroncoComun
                : ((bool) $gradoCurricular->pivot->obligatoria
                    ? TipoAsignatura::TroncoComun
                    : TipoAsignatura::Optativa);

            $filas[] = [
                'asignatura_id' => $fila['asignatura_id'],
                'asignatura_propia_clave' => null,
                'figura_profesional_id' => $fila['figura_profesional_id'],
                'area_id' => (int) $asignatura->area_id,
                'grado_id' => $fila['grado_id'],
                'tipo_asignatura' => $tipoAsignatura,
                'aparece_en_libreta' => $fila['aparece_en_libreta'],
                'horas_catalogo' => $asignatura->horas_semanales,
                'tipo_calificacion' => $calificacion['tipo_calificacion'],
                'califica_por_destrezas' => $calificacion['califica_por_destrezas'],
                'usa_tabla_equivalencias' => $calificacion['usa_tabla_equivalencias'],
                'porcentaje' => $fila['porcentaje'],
                'origen' => 'asignaturas.'.$fila['asignatura_id'],
            ];
        }

        return $filas;
    }

    /**
     * @return list<array{
     *     asignatura_id: null,
     *     asignatura_propia_clave: string,
     *     area_id: int,
     *     grado_id: int,
     *     tipo_asignatura: TipoAsignatura,
     *     aparece_en_libreta: bool,
     *     horas_catalogo: null,
     *     tipo_calificacion: TipoCalificacion,
     *     califica_por_destrezas: bool,
     *     usa_tabla_equivalencias: bool,
     *     porcentaje: float|null,
     *     origen: string
     * }>
     */
    private function filasPropiasPreparadas(): array
    {
        $filas = [];
        $grados = Sys_Grado::query()
            ->with('subnivel')
            ->whereIn('id', array_column($this->filasPropiasCandidatas(), 'grado_id'))
            ->get()
            ->keyBy('id');

        foreach ($this->filasPropiasCandidatas() as $fila) {
            $grado = $grados->get($fila['grado_id']);
            $tipo = TipoAsignatura::tryFrom($fila['tipo']);

            if ($grado === null || $tipo === null) {
                continue;
            }

            $calificacion = $this->calificacionDeFila(null, $grado, $fila);

            $filas[] = [
                'asignatura_id' => null,
                'asignatura_propia_clave' => $fila['clave'],
                'figura_profesional_id' => $fila['figura_profesional_id'],
                'area_id' => $fila['area_id'],
                'grado_id' => $fila['grado_id'],
                'tipo_asignatura' => $tipo,
                'aparece_en_libreta' => $fila['aparece_en_libreta'],
                'horas_catalogo' => null,
                'tipo_calificacion' => $calificacion['tipo_calificacion'],
                'califica_por_destrezas' => $calificacion['califica_por_destrezas'],
                'usa_tabla_equivalencias' => $calificacion['usa_tabla_equivalencias'],
                'porcentaje' => $fila['porcentaje'],
                'origen' => 'propias.'.$fila['clave'],
            ];
        }

        return $filas;
    }

    /**
     * @param  list<int>  $gradoIdsOferta
     * @param  Collection<int, int>  $subnivelPorGrado
     */
    private function validarPropias(Validator $validator, array $gradoIdsOferta, $subnivelPorGrado): void
    {
        if (! ($this->user()?->establecimiento?->bachillerato_tecnico ?? false)
            && collect($this->definicionesCandidatas())->contains(fn (array $definicion): bool => $definicion['figura_profesional_id'] !== null)) {
            $validator->errors()->add('propias', 'Activa Bachillerato Técnico en la ficha del establecimiento para vincular asignaturas con figuras profesionales.');

            return;
        }

        $nombres = [];
        $figurasPorClave = collect($this->definicionesCandidatas())
            ->mapWithKeys(fn (array $definicion): array => [$definicion['clave'] => $definicion['figura_profesional_id']]);

        foreach ($this->definicionesCandidatas() as $definicion) {
            if ($definicion['eliminar']) {
                continue;
            }

            $area = Sys_Area::query()->find($definicion['area_id']);

            if ($definicion['figura_profesional_id'] !== null) {
                $figuraValida = EstablecimientoFiguraProfesional::query()
                    ->whereKey($definicion['figura_profesional_id'])
                    ->where('establecimiento_modalidad_jornada_id', $this->oferta()->id)
                    ->exists();

                if (! $figuraValida) {
                    $validator->errors()->add(
                        'propias.'.$definicion['clave'].'.figura_profesional_id',
                        'Selecciona una figura profesional de la jornada y periodo activos.',
                    );

                    return;
                }
            }

            if ($area === null) {
                $validator->errors()->add(
                    'propias.'.$definicion['clave'].'.area_id',
                    'El área no existe en el catálogo.',
                );

                return;
            }

            $llave = $definicion['area_id'].'|'.mb_strtolower($definicion['nombre']);

            if (isset($nombres[$llave])) {
                $validator->errors()->add(
                    'propias.'.$definicion['clave'].'.nombre',
                    'Ya existe una asignatura con ese nombre en el área.',
                );

                return;
            }

            $nombres[$llave] = true;
            $id = $definicion['id'];

            if ($id !== null) {
                $pertenece = EstablecimientoAsignaturaPropia::query()
                    ->whereKey($id)
                    ->where('establecimiento_modalidad_jornada_id', $this->oferta()->id)
                    ->exists();

                if (! $pertenece) {
                    $validator->errors()->add(
                        'propias.'.$definicion['clave'].'.nombre',
                        'La asignatura no pertenece a esta jornada.',
                    );

                    return;
                }
            }
        }

        foreach ($this->filasPropiasCandidatas() as $fila) {
            if (! in_array($fila['grado_id'], $gradoIdsOferta, true)) {
                $validator->errors()->add(
                    'propias.'.$fila['clave'].'.grados',
                    'Solo puedes asignar materias a los grados que esta jornada ya ofertó en Estructura.',
                );

                return;
            }

            $area = Sys_Area::query()->find($fila['area_id']);
            $subnivelDelGrado = $subnivelPorGrado->get($fila['grado_id']);

            if ($figurasPorClave->get($fila['clave']) !== null
                && ! Sys_Subnivel::query()
                    ->whereKey($subnivelDelGrado)
                    ->where('nombre', 'Bachillerato General Unificado')
                    ->whereHas('nivel', fn ($query) => $query->where('nombre', 'Bachillerato'))
                    ->exists()) {
                $validator->errors()->add(
                    'propias.'.$fila['clave'].'.grados',
                    'Los módulos de una figura profesional se asignan a los cursos de Bachillerato General Unificado.',
                );

                return;
            }

            if ($area === null || (int) $area->subnivel_id !== (int) $subnivelDelGrado) {
                $validator->errors()->add(
                    'propias.'.$fila['clave'].'.grados',
                    'Cada asignatura debe pertenecer al mismo subnivel del grado seleccionado.',
                );

                return;
            }
        }
    }

    /**
     * @return list<array{clave: string, id: int|null, nombre: string, area_id: int, tipo: string|null, figura_profesional_id: int|null, eliminar: bool}>
     */
    private function definicionesCandidatas(): array
    {
        /** @var array<int|string, mixed> $raw */
        $raw = $this->input('propias', []);
        $definiciones = [];

        foreach ($raw as $clave => $payload) {
            if (! is_array($payload)) {
                continue;
            }

            $clave = (string) $clave;
            $areaId = (int) ($payload['area_id'] ?? 0);
            $tipo = $payload['tipo'] ?? null;

            $definiciones[] = [
                'clave' => $clave,
                'id' => ctype_digit($clave) ? (int) $clave : null,
                'nombre' => trim((string) ($payload['nombre'] ?? '')),
                'area_id' => $areaId,
                'tipo' => is_string($tipo) && $tipo !== '' ? $tipo : null,
                'figura_profesional_id' => is_numeric($payload['figura_profesional_id'] ?? null) && (int) $payload['figura_profesional_id'] > 0
                    ? (int) $payload['figura_profesional_id']
                    : null,
                'eliminar' => filter_var($payload['eliminar'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        return $definiciones;
    }

    /**
     * @return list<array{clave: string, area_id: int, grado_id: int, tipo: string, aparece_en_libreta: bool, tipo_calificacion: string|null, califica_por_destrezas: bool, usa_tabla_equivalencias: bool, porcentaje: float|null}>
     */
    private function filasPropiasCandidatas(): array
    {
        $filas = [];

        foreach ($this->definicionesCandidatas() as $definicion) {
            if ($definicion['eliminar'] || $definicion['tipo'] === null) {
                continue;
            }

            /** @var array<string, mixed> $payload */
            $payload = $this->input('propias.'.$definicion['clave'], []);
            $gradoIds = array_values(array_unique(array_map(intval(...), $payload['grados'] ?? [])));

            foreach ($gradoIds as $gradoId) {
                if ($gradoId < 1) {
                    continue;
                }

                $tipo = $payload['tipo_calificacion'][$gradoId] ?? $payload['tipo_calificacion'][(string) $gradoId] ?? null;
                $libreta = $payload['libreta'][$gradoId] ?? $payload['libreta'][(string) $gradoId] ?? true;
                $destrezas = $payload['califica_por_destrezas'][$gradoId] ?? $payload['califica_por_destrezas'][(string) $gradoId] ?? false;
                $equivalencias = $payload['usa_tabla_equivalencias'][$gradoId] ?? $payload['usa_tabla_equivalencias'][(string) $gradoId] ?? false;
                $porcentaje = $payload['porcentaje'][$gradoId] ?? $payload['porcentaje'][(string) $gradoId] ?? null;

                $filas[] = [
                    'clave' => $definicion['clave'],
                    'figura_profesional_id' => $definicion['figura_profesional_id'],
                    'area_id' => $definicion['area_id'],
                    'grado_id' => $gradoId,
                    'tipo' => $definicion['tipo'],
                    'aparece_en_libreta' => filter_var($libreta, FILTER_VALIDATE_BOOLEAN),
                    'tipo_calificacion' => is_string($tipo) && $tipo !== '' ? $tipo : null,
                    'califica_por_destrezas' => filter_var($destrezas, FILTER_VALIDATE_BOOLEAN),
                    'usa_tabla_equivalencias' => filter_var($equivalencias, FILTER_VALIDATE_BOOLEAN),
                    'porcentaje' => is_numeric($porcentaje) ? (float) $porcentaje : null,
                ];
            }
        }

        return $filas;
    }

    /**
     * @return list<array{asignatura_id: int, grado_id: int, aparece_en_libreta: bool, horas_semanales: int|null, tipo_calificacion: string|null, califica_por_destrezas: bool, usa_tabla_equivalencias: bool, porcentaje: float|null}>
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
                $destrezas = $payload['califica_por_destrezas'][$gradoId] ?? $payload['califica_por_destrezas'][(string) $gradoId] ?? false;
                $equivalencias = $payload['usa_tabla_equivalencias'][$gradoId] ?? $payload['usa_tabla_equivalencias'][(string) $gradoId] ?? false;
                $porcentaje = $payload['porcentaje'][$gradoId] ?? $payload['porcentaje'][(string) $gradoId] ?? null;
                $figuraProfesionalId = $payload['figura_profesional_id'][$gradoId] ?? $payload['figura_profesional_id'][(string) $gradoId] ?? null;

                $filas[] = [
                    'asignatura_id' => (int) $asignaturaId,
                    'figura_profesional_id' => is_numeric($figuraProfesionalId) && (int) $figuraProfesionalId > 0 ? (int) $figuraProfesionalId : null,
                    'grado_id' => $gradoId,
                    'aparece_en_libreta' => filter_var($libreta, FILTER_VALIDATE_BOOLEAN),
                    'horas_semanales' => is_numeric($horas) ? (int) $horas : null,
                    'tipo_calificacion' => is_string($tipo) && $tipo !== '' ? $tipo : null,
                    'califica_por_destrezas' => filter_var($destrezas, FILTER_VALIDATE_BOOLEAN),
                    'usa_tabla_equivalencias' => filter_var($equivalencias, FILTER_VALIDATE_BOOLEAN),
                    'porcentaje' => is_numeric($porcentaje) ? (float) $porcentaje : null,
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

    /**
     * @param  array{tipo_calificacion: string|null, califica_por_destrezas: bool, usa_tabla_equivalencias: bool}  $fila
     * @return array{tipo_calificacion: TipoCalificacion, califica_por_destrezas: bool, usa_tabla_equivalencias: bool}
     */
    private function calificacionDeFila(?Sys_Asignatura $asignatura, Sys_Grado $grado, array $fila): array
    {
        $posted = $fila['tipo_calificacion'];

        if (is_string($posted) && TipoCalificacion::tryFrom($posted) instanceof TipoCalificacion) {
            $tipo = TipoCalificacion::from($posted);

            return [
                'tipo_calificacion' => $tipo,
                'califica_por_destrezas' => $tipo === TipoCalificacion::Cualitativa && $fila['califica_por_destrezas'],
                'usa_tabla_equivalencias' => $tipo === TipoCalificacion::Cuantitativa && $fila['usa_tabla_equivalencias'],
            ];
        }

        $subnivel = $grado->subnivel ?? $asignatura->area?->subnivel;
        $tipo = $subnivel?->tipo_calificacion ?? TipoCalificacion::Cuantitativa;

        return [
            'tipo_calificacion' => $tipo,
            'califica_por_destrezas' => $tipo === TipoCalificacion::Cualitativa && (bool) $subnivel?->califica_por_destrezas,
            'usa_tabla_equivalencias' => false,
        ];
    }

    private function porcentajeDeFila(
        TipoCalificacion $tipo,
        int $areaId,
        int $gradoId,
        int $cantidad,
        ?float $porcentaje,
    ): ?string {
        if ($tipo !== TipoCalificacion::Cuantitativa || ! $this->usaPromedioPorcentual($areaId, $gradoId, $cantidad) || $porcentaje === null) {
            return null;
        }

        return number_format($porcentaje, 2, '.', '');
    }

    private function usaPromedioPorcentual(int $areaId, int $gradoId, int $cantidad): bool
    {
        if ($cantidad < 2) {
            return false;
        }

        $posted = $this->modosAreaCandidatosIndexados();
        $modo = ModoLibretaArea::tryFrom($posted[$areaId][$gradoId] ?? '') ?? ModoLibretaArea::Asignaturas;

        return $modo->promediaElArea() && $this->promedioPorcentualPosteado($areaId, $gradoId);
    }

    private function promedioPorcentualPosteado(int $areaId, int $gradoId): bool
    {
        /** @var array<int|string, mixed> $raw */
        $raw = $this->input('areas', []);
        $payload = $raw[$areaId]['grados'][$gradoId]
            ?? $raw[(string) $areaId]['grados'][(string) $gradoId]
            ?? $raw[$areaId]['grados'][(string) $gradoId]
            ?? $raw[(string) $areaId]['grados'][$gradoId]
            ?? null;
        $valor = is_array($payload) ? ($payload['promedio_porcentual'] ?? false) : false;

        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  list<int>  $gradoIdsOferta
     */
    private function validarAsignaturasObligatorias(Validator $validator, array $gradoIdsOferta): void
    {
        $seleccionadas = [];

        foreach ($this->filasCandidatas() as $fila) {
            $seleccionadas[$fila['grado_id']][$fila['asignatura_id']] = true;
        }

        $grados = Sys_Grado::query()
            ->with(['asignaturasCurriculares' => fn ($query) => $query->wherePivot('obligatoria', true)])
            ->whereIn('id', $gradoIdsOferta)
            ->get();

        foreach ($grados as $grado) {
            foreach ($grado->asignaturasCurriculares as $asignatura) {
                if (isset($seleccionadas[$grado->id][$asignatura->id])) {
                    continue;
                }

                $validator->errors()->add(
                    'asignaturas',
                    'Debes incluir '.$asignatura->nombre.' en '.$grado->nombre.' porque pertenece al tronco común obligatorio.',
                );
            }
        }
    }

    /** @param array{asignatura_id: int, figura_profesional_id: int|null, grado_id: int} $fila */
    private function validarAsignaturaTecnica(Validator $validator, array $fila, Sys_Asignatura $asignatura, Sys_Grado $grado): void
    {
        $campo = 'asignaturas.'.$fila['asignatura_id'].'.figura_profesional_id.'.$fila['grado_id'];
        $esBachillerato = $grado->subnivel?->nombre === 'Bachillerato General Unificado'
            && $grado->subnivel?->nivel?->nombre === 'Bachillerato';
        $areaActiva = $this->oferta()->areasTecnicas()->whereKey($asignatura->area_id)->exists();

        if (! ($this->user()?->establecimiento?->bachillerato_tecnico ?? false)) {
            $validator->errors()->add($campo, 'Activa Bachillerato Técnico en la ficha del establecimiento para configurar módulos técnicos.');

            return;
        }

        if (! $esBachillerato || ! $areaActiva) {
            $validator->errors()->add($campo, 'Las áreas técnicas deben estar activadas en esta jornada y pertenecen a los cursos de BGU.');

            return;
        }

        $figuraId = $fila['figura_profesional_id'];
        if ($figuraId === null) {
            $validator->errors()->add($campo, 'Selecciona la figura profesional a la que pertenece este módulo técnico.');

            return;
        }

        $figuraValida = EstablecimientoFiguraProfesional::query()
            ->whereKey($figuraId)
            ->where('establecimiento_modalidad_jornada_id', $this->oferta()->id)
            ->exists();

        if (! $figuraValida) {
            $validator->errors()->add($campo, 'Selecciona una figura profesional de esta jornada.');
        }
    }

    private function contieneConfiguracionTecnica(): bool
    {
        foreach ($this->input('propias', []) as $propia) {
            if (is_array($propia) && ! empty($propia['figura_profesional_id'])) {
                return true;
            }
        }

        foreach ($this->input('asignaturas', []) as $asignatura) {
            foreach (($asignatura['figura_profesional_id'] ?? []) as $figuraId) {
                if (is_numeric($figuraId) && (int) $figuraId > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private function validarPorcentajes(Validator $validator): void
    {
        $filas = $this->filas();
        $modos = [];

        foreach ($this->modosArea() as $modo) {
            $modos[$modo['area_id']][$modo['grado_id']] = $modo;
        }

        $grupos = [];

        foreach ($filas as $fila) {
            $areaId = (int) $fila['area_id'];

            if ($areaId < 1) {
                continue;
            }

            $grupos[$areaId][$fila['grado_id']][] = $fila;
        }

        foreach ($grupos as $areaId => $grados) {
            foreach ($grados as $gradoId => $items) {
                if (count($items) < 2) {
                    continue;
                }

                $modo = $modos[$areaId][$gradoId] ?? null;

                if ($modo === null || ! $modo['promedio_porcentual']) {
                    continue;
                }

                $hayPorcentajes = array_filter(
                    $items,
                    fn (array $fila): bool => $fila['porcentaje'] !== null,
                ) !== [];

                if (! $hayPorcentajes) {
                    continue;
                }

                $cuantitativas = array_values(array_filter(
                    $items,
                    fn (array $fila): bool => $fila['tipo_calificacion'] === TipoCalificacion::Cuantitativa,
                ));

                if ($cuantitativas === []) {
                    $validator->errors()->add(
                        'areas.'.$areaId.'.grados.'.$gradoId.'.promedio_porcentual',
                        'El promedio por porcentajes necesita asignaturas cuantitativas.',
                    );

                    continue;
                }

                $faltan = false;
                $suma = 0.0;

                foreach ($cuantitativas as $fila) {
                    if ($fila['porcentaje'] === null) {
                        $faltan = true;
                        $validator->errors()->add(
                            $fila['origen'].'.porcentaje.'.$fila['grado_id'],
                            'Indica el porcentaje de esta asignatura en el área.',
                        );

                        continue;
                    }

                    $suma += (float) $fila['porcentaje'];
                }

                if (! $faltan && abs(round($suma, 2) - 100.0) > 0.009) {
                    $validator->errors()->add(
                        'areas.'.$areaId.'.grados.'.$gradoId.'.promedio_porcentual',
                        'Los porcentajes de las asignaturas cuantitativas deben sumar 100%.',
                    );
                }
            }
        }
    }
}
