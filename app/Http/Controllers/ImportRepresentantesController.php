<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportRepresentantesRequest;
use App\Models\ImportData;
use App\Services\ImportRepresentantesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportRepresentantesController extends Controller
{
    public function create(Request $request): View
    {
        $this->establecimientoId($request);

        $tipo = $request->string('tipo')->toString();

        if (! in_array($tipo, ['padres', 'alumnos', 'docentes'], true)) {
            $tipo = 'padres';
        }

        return view('admin.padres.import', [
            'tipo' => $tipo,
        ]);
    }

    public function store(
        ImportRepresentantesRequest $request,
        ImportRepresentantesService $importRepresentantes,
    ): RedirectResponse {
        $file = $request->file('archivo');
        $actor = $request->user();

        abort_if($file === null || $actor === null, 403);

        $import = $importRepresentantes->import(
            $actor,
            $file,
            $request->string('tipo')->toString(),
        );

        return redirect()
            ->route('Admin.padres.import.show', $import)
            ->with('status', 'import-processed');
    }

    public function show(Request $request, ImportData $importData): View
    {
        abort_unless(
            (int) $importData->establecimiento_id === $this->establecimientoId($request),
            404,
        );

        $importData->loadMissing('detalles');

        return view('admin.padres.import-result', [
            'importData' => $importData,
        ]);
    }

    private function establecimientoId(Request $request): int
    {
        $establecimientoId = $request->user()?->establecimiento_id;

        abort_if($establecimientoId === null, 403);

        return (int) $establecimientoId;
    }
}
