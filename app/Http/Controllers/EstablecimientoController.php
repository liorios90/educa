<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertEstablecimientoRequest;
use App\Models\Establecimiento;
use App\Models\Sys_Circuito;
use App\Models\Sys_Distrito;
use App\Models\Sys_Zona;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EstablecimientoController extends Controller
{
    public function index(): View
    {
        return view('sistemas.establecimientos.index', [
            'establecimientos' => Establecimiento::query()
                ->with(['zona:id,nombre', 'distrito:id,nombre', 'circuito:id,nombre'])
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('sistemas.establecimientos.create', $this->catalogOptions());
    }

    public function store(UpsertEstablecimientoRequest $request): RedirectResponse
    {
        Establecimiento::query()->create($request->safe()->only($this->fillableAttributes()));

        return redirect()
            ->route('sistemas.establecimientos')
            ->with('status', 'establecimiento-created');
    }

    public function edit(Establecimiento $establecimiento): View
    {
        return view('sistemas.establecimientos.edit', [
            ...$this->catalogOptions(),
            'establecimiento' => $establecimiento,
        ]);
    }

    public function update(UpsertEstablecimientoRequest $request, Establecimiento $establecimiento): RedirectResponse
    {
        $establecimiento->update($request->safe()->only($this->fillableAttributes()));

        return redirect()
            ->route('sistemas.establecimientos')
            ->with('status', 'establecimiento-updated');
    }

    public function destroy(Establecimiento $establecimiento): RedirectResponse
    {
        $establecimiento->delete();

        return redirect()
            ->route('sistemas.establecimientos')
            ->with('status', 'establecimiento-deleted');
    }

    /**
     * @return array{
     *     zonas: list<array{id: int, nombre: string}>,
     *     distritos: list<array{id: int, nombre: string, zona_id: int}>,
     *     circuitos: list<array{id: int, nombre: string, distrito_id: int|null}>
     * }
     */
    private function catalogOptions(): array
    {
        return [
            'zonas' => Sys_Zona::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->map(fn (Sys_Zona $zona): array => [
                    'id' => $zona->id,
                    'nombre' => $zona->nombre,
                ])
                ->values()
                ->all(),
            'distritos' => Sys_Distrito::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'zona_id'])
                ->map(fn (Sys_Distrito $distrito): array => [
                    'id' => $distrito->id,
                    'nombre' => $distrito->nombre,
                    'zona_id' => $distrito->zona_id,
                ])
                ->values()
                ->all(),
            'circuitos' => Sys_Circuito::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'distrito_id'])
                ->map(fn (Sys_Circuito $circuito): array => [
                    'id' => $circuito->id,
                    'nombre' => $circuito->nombre,
                    'distrito_id' => $circuito->distrito_id,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<string>
     */
    private function fillableAttributes(): array
    {
        return [
            'nombre',
            'descripcion',
            'direccion',
            'telefono',
            'representante',
            'codigo_amie',
            'regimen',
            'email',
            'usuario',
            'activo',
            'logo',
            'only_visible',
            'mision',
            'vision',
            'ideario',
            'grupo_amie',
            'zona_id',
            'distrito_id',
            'circuito_id',
        ];
    }
}
