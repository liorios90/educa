<?php

namespace App\Http\Controllers;

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
    public function index(Request $request): View
    {
        $establecimiento = $this->establecimiento($request);

        return view('admin.asignaturas.index', [
            'establecimiento' => $establecimiento,
            'modalidades' => $establecimiento->establecimientoModalidades()
                ->with([
                    'modalidad',
                    'establecimientoJornadas' => fn ($query) => $query
                        ->with('jornada')
                        ->withCount(['grados', 'malla'])
                        ->orderBy('id'),
                ])
                ->orderBy('id')
                ->get(),
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
        ]);

        $gradosOferta = $oferta->grados;
        $subnivelIds = $gradosOferta->pluck('subnivel_id')->filter()->unique()->values();

        $subniveles = Sys_Subnivel::query()
            ->with(['nivel', 'areas.asignaturas'])
            ->whereIn('id', $subnivelIds)
            ->orderBy('id')
            ->get();

        return view('admin.asignaturas.edit', [
            'establecimiento' => $establecimiento,
            'oferta' => $oferta,
            'subniveles' => $subniveles,
            'gradosPorSubnivel' => $gradosOferta->groupBy('subnivel_id'),
            'selectedGradosPorAsignatura' => $this->selectedGradosPorAsignatura($oferta->malla, $subniveles),
            'configPorAsignatura' => $this->configPorAsignatura($oferta->malla),
            'modosPorArea' => $this->modosPorArea($oferta->areaLibretas),
        ]);
    }

    public function update(
        SyncEstablecimientoAsignaturasRequest $request,
        EstablecimientoModalidadJornada $oferta,
        SyncEstablecimientoAsignaturas $sync,
    ): RedirectResponse {
        $establecimiento = $this->establecimiento($request);
        $oferta = $this->ofertaDelEstablecimiento($establecimiento, $oferta);

        $sync->handle($establecimiento, $oferta, $request->filas(), $request->modosArea());

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
     * @return array<string, list<string>>
     */
    private function selectedGradosPorAsignatura(Collection $malla, Collection $subniveles): array
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
                $gradoIds = is_array($payload['grados'] ?? null) ? $payload['grados'] : [];
                $selected[(string) $asignaturaId] = array_values(array_map(strval(...), $gradoIds));
            }

            return $selected;
        }

        foreach ($malla as $fila) {
            $asignaturaId = (string) $fila->asignatura_id;
            $selected[$asignaturaId] ??= [];
            $selected[$asignaturaId][] = (string) $fila->grado_id;
        }

        return $selected;
    }

    /**
     * @param  Collection<int, EstablecimientoAsignatura>  $malla
     * @return array<string, array<string, array{horas: string, libreta: bool, tipo: string}>>
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
                    ];
                }
            }

            return $config;
        }

        foreach ($malla as $fila) {
            $config[(string) $fila->asignatura_id][(string) $fila->grado_id] = [
                'horas' => $fila->horas_semanales === null ? '' : (string) $fila->horas_semanales,
                'libreta' => (bool) $fila->aparece_en_libreta,
                'tipo' => $fila->tipo_calificacion?->value ?? '',
            ];
        }

        return $config;
    }

    /**
     * @param  Collection<int, EstablecimientoAreaLibreta>  $filas
     * @return array<string, array<string, string>>
     */
    private function modosPorArea(Collection $filas): array
    {
        $modos = [];
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
                }
            }

            return $modos;
        }

        foreach ($filas as $fila) {
            $modos[(string) $fila->area_id][(string) $fila->grado_id] = $fila->modo_libreta?->value ?? '';
        }

        return $modos;
    }
}
