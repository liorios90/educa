<?php

namespace App\Http\Controllers\Admin;

use App\Auth\ActivePeriodo;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCriteriosComportamientoRequest;
use App\Models\EstablecimientoPeriodo;
use App\Models\Sys_CriterioComportamiento;
use App\Services\SeleccionCriteriosComportamiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CriterioComportamientoController extends Controller
{
    public function __construct(private ActivePeriodo $activePeriodo) {}

    public function edit(Request $request): View
    {
        $periodo = $this->activePeriodo->get($request->user());
        $seleccionados = $periodo === null
            ? []
            : $periodo->criteriosComportamiento()
                ->pluck('sys_criterios_comportamiento.id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

        $criterios = $periodo === null
            ? collect()
            : Sys_CriterioComportamiento::query()->activo()->orderBy('tipo')->orderBy('codigo')->get();

        return view('admin.criterios-comportamiento.edit', [
            'periodo' => $periodo,
            'grupos' => $criterios->groupBy(
                fn (Sys_CriterioComportamiento $criterio): string => $criterio->tipo->value,
            ),
            'marcados' => collect(old('criterios', $seleccionados))
                ->map(fn (mixed $id): int => (int) $id)
                ->all(),
            'cupos' => SeleccionCriteriosComportamiento::cuposOpcionales(),
        ]);
    }

    public function update(
        UpdateCriteriosComportamientoRequest $request,
        SeleccionCriteriosComportamiento $seleccion,
    ): RedirectResponse {
        $seleccion->guardar($this->periodoActivo(), $request->validated('criterios') ?? []);

        return redirect()
            ->route('Admin.criterios-comportamiento.edit')
            ->with('status', 'criterios-comportamiento-updated');
    }

    private function periodoActivo(): EstablecimientoPeriodo
    {
        $periodo = $this->activePeriodo->get(request()->user());

        abort_if($periodo === null, 404);

        return $periodo;
    }
}
