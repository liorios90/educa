<?php

namespace App\Http\Controllers\Admin;

use App\Auth\ActivePeriodo;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarCierresParcialesRequest;
use App\Models\EstablecimientoPeriodoCicloParcial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProcesoCierresController extends Controller
{
    public function __construct(private ActivePeriodo $activePeriodo) {}

    public function index(): View
    {
        $periodo = $this->activePeriodo->get(request()->user());
        $periodo?->load('ciclos.parciales');

        $parciales = $periodo?->ciclos->flatMap(fn ($ciclo) => $ciclo->parciales->map(fn (EstablecimientoPeriodoCicloParcial $parcial): array => [
            'id' => $parcial->id,
            'ciclo' => $ciclo->nombre,
            'numero' => $parcial->numero,
            'cerrado' => $parcial->cerrado_en !== null,
        ]))->values() ?? collect();
        $ciclosFormulario = $periodo?->ciclos->map(fn ($ciclo): array => [
            'nombre' => $ciclo->nombre,
            'parciales' => $ciclo->parciales->map(fn (EstablecimientoPeriodoCicloParcial $parcial): array => [
                'id' => $parcial->id,
                'numero' => $parcial->numero,
            ])->all(),
        ])->all() ?? [];
        $parcialesAnteriores = old('parciales_cerrados');
        $parcialesSeleccionados = is_array($parcialesAnteriores)
            ? collect($parcialesAnteriores)->filter(fn (mixed $id): bool => is_scalar($id))->map(fn (mixed $id): string => (string) $id)->all()
            : $parciales->where('cerrado', true)->pluck('id')->map(fn (int $id): string => (string) $id)->all();

        return view('admin.proceso-cierres.index', [
            'periodo' => $periodo,
            'parciales' => $parciales,
            'ciclosFormulario' => $ciclosFormulario,
            'parcialesSeleccionados' => $parcialesSeleccionados,
            'nombreEsquema' => $periodo?->nombre_esquema ?: $periodo?->esquema_ciclo?->nombrePorDefecto() ?: 'Esquema de trabajo',
        ]);
    }

    public function update(ActualizarCierresParcialesRequest $request): RedirectResponse
    {
        $periodo = $request->periodo();
        abort_if($periodo === null, 403);

        $idsCerrados = array_map('intval', $request->validated('parciales_cerrados', []));

        DB::transaction(function () use ($periodo, $idsCerrados): void {
            EstablecimientoPeriodoCicloParcial::query()
                ->whereHas('ciclo', fn ($query) => $query->where('establecimiento_periodo_id', $periodo->id))
                ->get()
                ->each(function (EstablecimientoPeriodoCicloParcial $parcial) use ($idsCerrados): void {
                    $cerrado = in_array($parcial->id, $idsCerrados, true);
                    $parcial->update([
                        'cerrado_en' => $cerrado ? ($parcial->cerrado_en ?? now()) : null,
                    ]);
                });
        });

        return redirect()->route('Admin.proceso-cierres.index')->with('status', 'cierres-parciales-updated');
    }
}
