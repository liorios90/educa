<?php

namespace App\Http\Controllers;

use App\Http\Requests\SyncEstablecimientoEstructuraRequest;
use App\Models\Establecimiento;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\Sys_Nivel;
use App\Services\SyncEstablecimientoEstructura;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class EstablecimientoEstructuraController extends Controller
{
    public function index(Request $request): View
    {
        $establecimiento = $this->establecimiento($request);

        return view('admin.estructura.index', [
            'establecimiento' => $establecimiento,
            'modalidades' => $establecimiento->establecimientoModalidades()
                ->with([
                    'modalidad',
                    'establecimientoJornadas' => fn ($query) => $query
                        ->with('jornada')
                        ->withCount('grados')
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
            'subniveles',
        ]);

        return view('admin.estructura.edit', [
            'establecimiento' => $establecimiento,
            'oferta' => $oferta,
            'niveles' => Sys_Nivel::query()
                ->with(['subniveles.grados'])
                ->orderBy('id')
                ->get(),
            'selectedGradoIds' => $this->selectedIds(old('grados', $oferta->grados->modelKeys())),
            'nombresGrados' => $this->nombresMap(old('nombre_grados', $this->pivotNombres($oferta->grados))),
            'nombresSubniveles' => $this->nombresMap(old('nombre_subniveles', $this->pivotNombres($oferta->subniveles))),
        ]);
    }

    public function update(
        SyncEstablecimientoEstructuraRequest $request,
        EstablecimientoModalidadJornada $oferta,
        SyncEstablecimientoEstructura $sync,
    ): RedirectResponse {
        $establecimiento = $this->establecimiento($request);
        $oferta = $this->ofertaDelEstablecimiento($establecimiento, $oferta);

        $sync->handle(
            $establecimiento,
            $oferta,
            $request->gradoIds(),
            $request->nombresGrados(),
            $request->nombresSubniveles(),
        );

        return redirect()
            ->route('Admin.estructura.edit', $oferta)
            ->with('status', 'estructura-updated');
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
     * @param  list<mixed>  $ids
     * @return list<string>
     */
    private function selectedIds(array $ids): array
    {
        return collect($ids)
            ->map(fn (mixed $id): string => (string) $id)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Model>  $items
     * @return array<string, string>
     */
    private function pivotNombres(Collection $items): array
    {
        $nombres = [];

        foreach ($items as $item) {
            $nombres[(string) $item->getKey()] = (string) ($item->pivot->nombre ?? '');
        }

        return $nombres;
    }

    /**
     * @return array<string, string>
     */
    private function nombresMap(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $nombres = [];

        foreach ($values as $id => $nombre) {
            $nombres[(string) $id] = is_scalar($nombre) ? (string) $nombre : '';
        }

        return $nombres;
    }
}
