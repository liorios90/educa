<?php

namespace App\Http\Controllers;

use App\Models\AsistenteConsulta;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AsistenteConsultaController extends Controller
{
    public function index(): View
    {
        return view('sistemas.asistente-consultas.index', [
            'consultas' => AsistenteConsulta::query()
                ->with('establecimiento')
                ->latest()
                ->orderByDesc('id')
                ->paginate(20),
        ]);
    }

    public function destroy(AsistenteConsulta $asistenteConsulta): RedirectResponse
    {
        $asistenteConsulta->delete();

        return back()->with('status', 'consulta-eliminada');
    }
}
