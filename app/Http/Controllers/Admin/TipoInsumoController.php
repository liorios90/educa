<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoCalificacion;
use App\Http\Controllers\Controller;
use App\Models\TipoInsumo;
use App\Models\TipoInsumoDetalle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TipoInsumoController extends Controller
{
    public function index(): View
    {
        return view('admin.tipos-insumos.index', [
            'tiposInsumos' => TipoInsumo::query()->orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.tipos-insumos.form', [
            'tipoInsumo' => new TipoInsumo(['activo' => true, 'tipo_calificacion' => TipoCalificacion::Cuantitativa->value]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        TipoInsumo::query()->create($this->validatedAttributes($request));

        return redirect()->route('Admin.tipos-insumos.index')->with('status', 'tipos-insumos-created');
    }

    public function edit(TipoInsumo $tipoInsumo): View
    {
        return view('admin.tipos-insumos.form', compact('tipoInsumo'));
    }

    public function update(Request $request, TipoInsumo $tipoInsumo): RedirectResponse
    {
        $tipoInsumo->update($this->validatedAttributes($request));

        return redirect()->route('Admin.tipos-insumos.index')->with('status', 'tipos-insumos-updated');
    }

    public function destroy(TipoInsumo $tipoInsumo): RedirectResponse
    {
        $tipoInsumo->delete();

        return redirect()->route('Admin.tipos-insumos.index')->with('status', 'tipos-insumos-deleted');
    }

    public function detalles(TipoInsumo $tipoInsumo): View
    {
        $tipoInsumo->load(['detalles' => fn ($query) => $query->orderBy('id')]);

        return view('admin.tipos-insumos.detalles', [
            'tipoInsumo' => $tipoInsumo,
            'totalPorcentajeActivo' => (float) $tipoInsumo->detalles
                ->where('activo', true)
                ->sum(fn (TipoInsumoDetalle $detalle): float => (float) $detalle->porcentaje),
        ]);
    }

    public function storeDetalle(Request $request, TipoInsumo $tipoInsumo): RedirectResponse
    {
        $tipoInsumo->detalles()->create($this->validatedDetalleAttributes($request, $tipoInsumo));

        return redirect()->route('Admin.tipos-insumos.detalles.index', $tipoInsumo)->with('status', 'tipo-insumo-detalle-created');
    }

    public function updateDetalle(Request $request, TipoInsumo $tipoInsumo, TipoInsumoDetalle $detalle): RedirectResponse
    {
        $detalle->update($this->validatedDetalleAttributes($request, $tipoInsumo));

        return redirect()->route('Admin.tipos-insumos.detalles.index', $tipoInsumo)->with('status', 'tipo-insumo-detalle-updated');
    }

    public function destroyDetalle(TipoInsumo $tipoInsumo, TipoInsumoDetalle $detalle): RedirectResponse
    {
        $detalle->delete();

        return redirect()->route('Admin.tipos-insumos.detalles.index', $tipoInsumo)->with('status', 'tipo-insumo-detalle-deleted');
    }

    /**
     * @return array{nombre: string, descripcion: ?string, tipo_calificacion: string, activo: bool}
     */
    private function validatedAttributes(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'tipo_calificacion' => ['required', Rule::enum(TipoCalificacion::class)],
            'activo' => ['required', 'boolean'],
        ]);
    }

    /**
     * @return array{nombre: string, descripcion: ?string, valor: ?string, porcentaje: ?string, activo: bool}
     */
    private function validatedDetalleAttributes(Request $request, TipoInsumo $tipoInsumo): array
    {
        $rules = [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['required', 'boolean'],
        ];

        if ($tipoInsumo->tipo_calificacion === TipoCalificacion::Cuantitativa->value) {
            $rules['porcentaje'] = ['required', 'numeric', 'min:0', 'max:100'];
        } else {
            $rules['valor'] = ['required', 'string', 'max:100'];
        }

        $attributes = $request->validate($rules);

        return [
            'nombre' => $attributes['nombre'],
            'descripcion' => $attributes['descripcion'] ?? null,
            'valor' => $attributes['valor'] ?? null,
            'porcentaje' => $attributes['porcentaje'] ?? null,
            'activo' => (bool) $attributes['activo'],
        ];
    }
}
