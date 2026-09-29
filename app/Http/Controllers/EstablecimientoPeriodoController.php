<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertEstablecimientoPeriodoRequest;
use App\Models\Establecimiento;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EstablecimientoPeriodoController extends Controller
{
    public function index(Request $request, Establecimiento $establecimiento): View
    {
        $ofertas = $this->ofertas($establecimiento);

        return view('sistemas.establecimientos.periodos', [
            'establecimiento' => $establecimiento,
            'ofertas' => $ofertas,
            'openOfertaId' => $this->openOfertaId($request, $ofertas),
        ]);
    }

    public function store(UpsertEstablecimientoPeriodoRequest $request, Establecimiento $establecimiento): RedirectResponse
    {
        $ofertaId = $request->integer('establecimiento_modalidad_jornada_id');

        DB::transaction(function () use ($request, $establecimiento): void {
            $periodo = $establecimiento->periodos()->create($request->safe()->only($this->fillableAttributes()));
            $periodo->activarEnOferta();
        });

        return $this->redirectToPeriodos($establecimiento, $ofertaId, 'periodo-created');
    }

    public function update(
        UpsertEstablecimientoPeriodoRequest $request,
        Establecimiento $establecimiento,
        EstablecimientoPeriodo $periodo,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $periodo): void {
            $periodo->update($request->safe()->only($this->fillableAttributes()));
            $periodo->activarEnOferta();
        });

        return $this->redirectToPeriodos(
            $establecimiento,
            $request->integer('establecimiento_modalidad_jornada_id'),
            'periodo-updated',
        );
    }

    public function destroy(Establecimiento $establecimiento, EstablecimientoPeriodo $periodo): RedirectResponse
    {
        $ofertaId = $periodo->establecimiento_modalidad_jornada_id;
        $periodo->delete();

        return $this->redirectToPeriodos($establecimiento, $ofertaId, 'periodo-deleted');
    }

    /**
     * @return Collection<int, EstablecimientoModalidadJornada>
     */
    private function ofertas(Establecimiento $establecimiento): Collection
    {
        return EstablecimientoModalidadJornada::query()
            ->whereHas(
                'establecimientoModalidad',
                fn ($query) => $query->where('establecimiento_id', $establecimiento->id),
            )
            ->with([
                'jornada',
                'establecimientoModalidad.modalidad',
                'periodos' => fn ($query) => $query->orderByDesc('id'),
            ])
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, EstablecimientoModalidadJornada>  $ofertas
     */
    private function openOfertaId(Request $request, Collection $ofertas): ?int
    {
        $ids = $ofertas->pluck('id');

        foreach ($ofertas as $oferta) {
            if ($oferta->periodos->contains(fn (EstablecimientoPeriodo $periodo): bool => session()->get('errors')?->getBag('periodo-'.$periodo->id)->isNotEmpty() ?? false)) {
                return $oferta->id;
            }

            if (session()->get('errors')?->getBag('periodo-create-'.$oferta->id)->isNotEmpty()) {
                return $oferta->id;
            }
        }

        $solicitada = $request->integer('oferta');

        if ($solicitada > 0 && $ids->contains($solicitada)) {
            return $solicitada;
        }

        return $ids->first();
    }

    private function redirectToPeriodos(Establecimiento $establecimiento, int $ofertaId, string $status): RedirectResponse
    {
        return redirect()
            ->route('sistemas.establecimientos.periodos', [
                'establecimiento' => $establecimiento,
                'oferta' => $ofertaId,
            ])
            ->with('status', $status);
    }

    /**
     * @return list<string>
     */
    private function fillableAttributes(): array
    {
        return [
            'establecimiento_modalidad_jornada_id',
            'nombre',
            'fecha_inicio',
            'fecha_fin',
            'activo',
        ];
    }
}
