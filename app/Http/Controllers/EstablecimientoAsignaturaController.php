<?php

namespace App\Http\Controllers;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\TipoAsignatura;
use App\Http\Requests\SyncEstablecimientoAsignaturasRequest;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAreaLibreta;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\Sys_Subnivel;
use App\Services\SyncEstablecimientoAsignaturas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class EstablecimientoAsignaturaController extends Controller
{
    public function __construct(
        private ActiveOferta $activeOferta,
        private ActivePeriodo $activePeriodo,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $establecimiento = $this->establecimiento($request);
        $user = $request->user();

        if ($user !== null && $this->activeOferta->needsSelection($user)) {
            return redirect()->guest(route('oferta.select'));
        }

        $oferta = $this->activeOferta->get($user);
        $periodo = $this->activePeriodo->get($user);

        if ($oferta !== null && $periodo !== null) {
            return redirect()->route('Admin.asignaturas.edit', $oferta);
        }

        return view('admin.asignaturas.index', [
            'establecimiento' => $establecimiento,
            'oferta' => $oferta,
            'periodo' => $periodo,
        ]);
    }

    public function edit(Request $request, EstablecimientoModalidadJornada $oferta): View
    {
        $establecimiento = $this->establecimiento($request);
        $oferta = $this->ofertaDelEstablecimiento($establecimiento, $oferta)->load([
            'jornada',
            'establecimientoModalidad.modalidad',
            'grados',
            'malla',
            'areaLibretas',
            'asignaturasPropias',
        ]);

        $gradosOferta = $oferta->grados;
        $subnivelIds = $gradosOferta->pluck('subnivel_id')->filter()->unique()->values();

        $subniveles = Sys_Subnivel::query()
            ->with(['nivel', 'areas.asignaturas.gradosCurriculares'])
            ->whereIn('id', $subnivelIds)
            ->orderBy('id')
            ->get();

        $libretaPorArea = $this->libretaPorArea($oferta->areaLibretas);

        return view('admin.asignaturas.edit', [
            'establecimiento' => $establecimiento,
            'oferta' => $oferta,
            'subniveles' => $subniveles,
            'gradosPorSubnivel' => $gradosOferta->groupBy('subnivel_id'),
            'selectedGradosPorAsignatura' => $this->selectedGradosPorAsignatura(
                $oferta->malla,
                $subniveles,
                $gradosOferta->pluck('id')->map(strval(...))->all(),
            ),
            'configPorAsignatura' => $this->configPorAsignatura($oferta->malla),
            'propiasPorArea' => $this->propiasPorArea($oferta),
            'modosPorArea' => $libretaPorArea['modos'],
            'promedioPorcentualPorArea' => $libretaPorArea['porcentual'],
        ]);
    }

    public function update(
        SyncEstablecimientoAsignaturasRequest $request,
        EstablecimientoModalidadJornada $oferta,
        SyncEstablecimientoAsignaturas $sync,
    ): RedirectResponse {
        $establecimiento = $this->establecimiento($request);
        $oferta = $this->ofertaDelEstablecimiento($establecimiento, $oferta);

        $sync->handle(
            $establecimiento,
            $oferta,
            $request->filas(),
            $request->modosArea(),
            $request->definicionesPropias(),
        );

        return redirect()
            ->route('Admin.asignaturas.edit', $oferta)
            ->with('status', 'asignaturas-updated');
    }

    private function establecimiento(Request $request): Establecimiento
    {
        $establecimientoId = $request->user()?->establecimiento_id;

        abort_if($establecimientoId === null, 403);

        return Establecimiento::query()->findOrFail($establecimientoId);
    }

    private function ofertaDelEstablecimiento(
        Establecimiento $establecimiento,
        EstablecimientoModalidadJornada $oferta,
    ): EstablecimientoModalidadJornada {
        $oferta->loadMissing('establecimientoModalidad');

        abort_unless(
            (int) $oferta->establecimientoModalidad?->establecimiento_id === (int) $establecimiento->id,
            404,
        );

        return $oferta;
    }

    /**
     * @param  Collection<int, EstablecimientoAsignatura>  $malla
     * @param  Collection<int, Sys_Subnivel>  $subniveles
     * @param  list<string>  $gradoIdsOferta
     * @return array<string, list<string>>
     */
    private function selectedGradosPorAsignatura(Collection $malla, Collection $subniveles, array $gradoIdsOferta): array
    {
        $selected = [];

        foreach ($subniveles as $subnivel) {
            foreach ($subnivel->areas as $area) {
                foreach ($area->asignaturas as $asignatura) {
                    $selected[(string) $asignatura->id] = [];
                }
            }
        }

        $posted = old('asignaturas');

        if (is_array($posted)) {
            foreach ($posted as $asignaturaId => $payload) {
                if (! is_array($payload)) {
                    continue;
                }

                $gradoIds = is_array($payload['grados'] ?? null) ? $payload['grados'] : [];
                $selected[(string) $asignaturaId] = array_values(array_map(strval(...), $gradoIds));
            }
        } else {
            foreach ($malla as $fila) {
                if ($fila->asignatura_id === null) {
                    continue;
                }

                $asignaturaId = (string) $fila->asignatura_id;
                $selected[$asignaturaId] ??= [];
                $selected[$asignaturaId][] = (string) $fila->grado_id;
            }
        }

        foreach ($subniveles as $subnivel) {
            foreach ($subnivel->areas as $area) {
                foreach ($area->asignaturas as $asignatura) {
                    $obligatorios = $asignatura->gradosCurriculares
                        ->filter(fn ($grado): bool => (bool) $grado->pivot->obligatoria)
                        ->pluck('id')
                        ->map(strval(...))
                        ->filter(fn (string $gradoId): bool => in_array($gradoId, $gradoIdsOferta, true))
                        ->all();

                    $clave = (string) $asignatura->id;
                    $selected[$clave] = array_values(array_unique(array_merge($selected[$clave] ?? [], $obligatorios)));
                }
            }
        }

        return $this->agregarSeleccionPropia($selected, $malla, is_array($posted));
    }

    /**
     * @param  Collection<int, EstablecimientoAsignatura>  $malla
     * @return array<string, array<string, array{horas: string, libreta: bool, tipo: string, destrezas: bool, equivalencias: bool, porcentaje: string}>>
     */
    private function configPorAsignatura(Collection $malla): array
    {
        $config = [];
        $posted = old('asignaturas');

        if (is_array($posted)) {
            foreach ($posted as $asignaturaId => $payload) {
                if (! is_array($payload)) {
                    continue;
                }

                foreach (($payload['grados'] ?? []) as $gradoId) {
                    $config[(string) $asignaturaId][(string) $gradoId] = [
                        'horas' => (string) ($payload['horas'][$gradoId] ?? $payload['horas'][(string) $gradoId] ?? ''),
                        'libreta' => filter_var($payload['libreta'][$gradoId] ?? $payload['libreta'][(string) $gradoId] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'tipo' => (string) ($payload['tipo_calificacion'][$gradoId] ?? $payload['tipo_calificacion'][(string) $gradoId] ?? ''),
                        'destrezas' => filter_var($payload['califica_por_destrezas'][$gradoId] ?? $payload['califica_por_destrezas'][(string) $gradoId] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'equivalencias' => filter_var($payload['usa_tabla_equivalencias'][$gradoId] ?? $payload['usa_tabla_equivalencias'][(string) $gradoId] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'porcentaje' => (string) ($payload['porcentaje'][$gradoId] ?? $payload['porcentaje'][(string) $gradoId] ?? ''),
                    ];
                }
            }

        } else {
            foreach ($malla as $fila) {
                if ($fila->asignatura_id === null) {
                    continue;
                }

                $config[(string) $fila->asignatura_id][(string) $fila->grado_id] = $this->configDeFila($fila);
            }
        }

        return $this->agregarConfigPropia($config, $malla, is_array($posted));
    }

    /**
     * @return array{horas: string, libreta: bool, tipo: string, destrezas: bool, equivalencias: bool, porcentaje: string}
     */
    private function configDeFila(EstablecimientoAsignatura $fila): array
    {
        return [
            'horas' => $fila->horas_semanales === null ? '' : (string) $fila->horas_semanales,
            'libreta' => (bool) $fila->aparece_en_libreta,
            'tipo' => $fila->tipo_calificacion?->value ?? '',
            'destrezas' => (bool) $fila->califica_por_destrezas,
            'equivalencias' => (bool) $fila->usa_tabla_equivalencias,
            'porcentaje' => $fila->porcentaje === null ? '' : (string) $fila->porcentaje,
        ];
    }

    /**
     * @param  array<string, list<string>>  $selected
     * @param  Collection<int, EstablecimientoAsignatura>  $malla
     * @return array<string, list<string>>
     */
    private function agregarSeleccionPropia(array $selected, Collection $malla, bool $vieneDelFormulario): array
    {
        $posted = old('propias');

        if (is_array($posted)) {
            foreach ($posted as $clave => $payload) {
                if (! is_array($payload)) {
                    continue;
                }

                $alpine = ctype_digit((string) $clave) ? 'p'.$clave : (string) $clave;
                $gradoIds = is_array($payload['grados'] ?? null) ? $payload['grados'] : [];
                $selected[$alpine] = array_values(array_map(strval(...), $gradoIds));
            }

            return $selected;
        }

        if ($vieneDelFormulario) {
            return $selected;
        }

        foreach ($malla as $fila) {
            if ($fila->asignatura_propia_id === null) {
                continue;
            }

            $alpine = 'p'.$fila->asignatura_propia_id;
            $selected[$alpine] ??= [];
            $selected[$alpine][] = (string) $fila->grado_id;
        }

        return $selected;
    }

    /**
     * @param  array<string, array<string, array{horas: string, libreta: bool, tipo: string, destrezas: bool, equivalencias: bool, porcentaje: string}>>  $config
     * @param  Collection<int, EstablecimientoAsignatura>  $malla
     * @return array<string, array<string, array{horas: string, libreta: bool, tipo: string, destrezas: bool, equivalencias: bool, porcentaje: string}>>
     */
    private function agregarConfigPropia(array $config, Collection $malla, bool $vieneDelFormulario): array
    {
        $posted = old('propias');

        if (is_array($posted)) {
            foreach ($posted as $clave => $payload) {
                if (! is_array($payload)) {
                    continue;
                }

                $alpine = ctype_digit((string) $clave) ? 'p'.$clave : (string) $clave;

                foreach (($payload['grados'] ?? []) as $gradoId) {
                    $config[$alpine][(string) $gradoId] = [
                        'horas' => '',
                        'libreta' => filter_var($payload['libreta'][$gradoId] ?? $payload['libreta'][(string) $gradoId] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'tipo' => (string) ($payload['tipo_calificacion'][$gradoId] ?? $payload['tipo_calificacion'][(string) $gradoId] ?? ''),
                        'destrezas' => filter_var($payload['califica_por_destrezas'][$gradoId] ?? $payload['califica_por_destrezas'][(string) $gradoId] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'equivalencias' => filter_var($payload['usa_tabla_equivalencias'][$gradoId] ?? $payload['usa_tabla_equivalencias'][(string) $gradoId] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'porcentaje' => (string) ($payload['porcentaje'][$gradoId] ?? $payload['porcentaje'][(string) $gradoId] ?? ''),
                    ];
                }
            }

            return $config;
        }

        if ($vieneDelFormulario) {
            return $config;
        }

        foreach ($malla as $fila) {
            if ($fila->asignatura_propia_id === null) {
                continue;
            }

            $config['p'.$fila->asignatura_propia_id][(string) $fila->grado_id] = $this->configDeFila($fila);
        }

        return $config;
    }

    /**
     * @return array<string, list<array{clave: string, alpine: string, nombre: string, tipo: string, eliminar: bool, nueva: bool}>>
     */
    private function propiasPorArea(EstablecimientoModalidadJornada $oferta): array
    {
        $posted = old('propias');
        $grupos = [];

        if (is_array($posted)) {
            foreach ($posted as $clave => $payload) {
                if (! is_array($payload)) {
                    continue;
                }

                $areaId = (string) ($payload['area_id'] ?? '');
                $grupos[$areaId][] = [
                    'clave' => (string) $clave,
                    'alpine' => ctype_digit((string) $clave) ? 'p'.$clave : (string) $clave,
                    'nombre' => (string) ($payload['nombre'] ?? ''),
                    'tipo' => (string) ($payload['tipo'] ?? TipoAsignatura::Optativa->value),
                    'eliminar' => filter_var($payload['eliminar'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'nueva' => ! ctype_digit((string) $clave),
                ];
            }

            return $grupos;
        }

        foreach ($oferta->asignaturasPropias as $propia) {
            $grupos[(string) $propia->area_id][] = [
                'clave' => (string) $propia->id,
                'alpine' => 'p'.$propia->id,
                'nombre' => $propia->nombre,
                'tipo' => $propia->tipo_asignatura?->value ?? TipoAsignatura::Optativa->value,
                'eliminar' => false,
                'nueva' => false,
            ];
        }

        return $grupos;
    }

    /**
     * @param  Collection<int, EstablecimientoAreaLibreta>  $filas
     * @return array{modos: array<string, array<string, string>>, porcentual: array<string, array<string, bool>>}
     */
    private function libretaPorArea(Collection $filas): array
    {
        $modos = [];
        $porcentual = [];
        $posted = old('areas');

        if (is_array($posted)) {
            foreach ($posted as $areaId => $payload) {
                if (! is_array($payload)) {
                    continue;
                }

                foreach ($payload['grados'] ?? [] as $gradoId => $gradoPayload) {
                    $valor = is_array($gradoPayload) ? ($gradoPayload['modo_libreta'] ?? '') : '';

                    if (! is_string($valor) || $valor === '') {
                        continue;
                    }

                    $modos[(string) $areaId][(string) $gradoId] = $valor;
                    $porcentual[(string) $areaId][(string) $gradoId] = filter_var(
                        is_array($gradoPayload) ? ($gradoPayload['promedio_porcentual'] ?? false) : false,
                        FILTER_VALIDATE_BOOLEAN,
                    );
                }
            }

            return ['modos' => $modos, 'porcentual' => $porcentual];
        }

        foreach ($filas as $fila) {
            $modos[(string) $fila->area_id][(string) $fila->grado_id] = $fila->modo_libreta?->value ?? '';
            $porcentual[(string) $fila->area_id][(string) $fila->grado_id] = (bool) $fila->promedio_porcentual;
        }

        return ['modos' => $modos, 'porcentual' => $porcentual];
    }
}
