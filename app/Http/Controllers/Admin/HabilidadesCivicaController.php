<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateHabilidadesCivicaRequest;
use App\Models\Establecimiento;
use App\Services\AcompanamientoCivica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HabilidadesCivicaController extends Controller
{
    public function edit(Request $request, AcompanamientoCivica $civica): View
    {
        $establecimiento = $this->establecimiento($request);

        return view('admin.habilidades-civica.edit', [
            'establecimiento' => $establecimiento,
            'obligatorias' => $civica->obligatorias(),
            'opciones' => $civica->opciones(),
            'elegidas' => $civica->codigosElegidos((int) $establecimiento->id),
        ]);
    }

    public function update(UpdateHabilidadesCivicaRequest $request, AcompanamientoCivica $civica): RedirectResponse
    {
        $establecimiento = $request->establecimiento();

        abort_if($establecimiento === null, 404);

        /** @var list<string> $habilidades */
        $habilidades = $request->validated('habilidades');
        $civica->guardar($establecimiento, $habilidades);

        return redirect()
            ->route('Admin.habilidades-civica.edit')
            ->with('status', 'habilidades-civica-updated');
    }

    private function establecimiento(Request $request): Establecimiento
    {
        $establecimientoId = $request->user()?->establecimiento_id;

        abort_if($establecimientoId === null, 403);

        return Establecimiento::query()->findOrFail($establecimientoId);
    }
}
