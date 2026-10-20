<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEstablecimientoAsistenteRequest;
use App\Models\Establecimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfiguracionAsistenteController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.asistente.edit', [
            'establecimiento' => $this->establecimiento($request),
        ]);
    }

    public function update(UpdateEstablecimientoAsistenteRequest $request): RedirectResponse
    {
        $establecimiento = $request->establecimiento();

        abort_if($establecimiento === null, 404);

        $datos = $request->safe()->only([
            'gemini_model',
            'gemini_preguntas_limite',
            'gemini_tokens_limite',
        ]);

        if ($request->filled('gemini_api_key')) {
            $datos['gemini_api_key'] = $request->validated('gemini_api_key');
        }

        $establecimiento->update($datos);

        return redirect()
            ->route('Admin.asistente')
            ->with('status', 'asistente-updated');
    }

    private function establecimiento(Request $request): Establecimiento
    {
        $establecimientoId = $request->user()?->establecimiento_id;

        abort_if($establecimientoId === null, 403);

        return Establecimiento::query()->findOrFail($establecimientoId);
    }
}
