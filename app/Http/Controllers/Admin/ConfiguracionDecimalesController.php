<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ModoDecimal;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEstablecimientoDecimalesRequest;
use App\Models\Establecimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfiguracionDecimalesController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.decimales.edit', [
            'establecimiento' => $this->establecimiento($request),
            'modos' => ModoDecimal::cases(),
        ]);
    }

    public function update(UpdateEstablecimientoDecimalesRequest $request): RedirectResponse
    {
        $establecimiento = $request->establecimiento();

        abort_if($establecimiento === null, 404);

        $establecimiento->update($request->safe()->only([
            'decimales_calificacion',
            'modo_decimal',
        ]));

        return redirect()
            ->route('Admin.decimales')
            ->with('status', 'decimales-updated');
    }

    private function establecimiento(Request $request): Establecimiento
    {
        $establecimientoId = $request->user()?->establecimiento_id;

        abort_if($establecimientoId === null, 403);

        return Establecimiento::query()->findOrFail($establecimientoId);
    }
}
