<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertCurriculoAreaRequest;
use App\Http\Requests\UpsertCurriculoAsignaturaRequest;
use App\Models\NavigationItem;
use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CurriculoController extends Controller
{
    public function index(Request $request): View
    {
        return view('sistemas.curriculo.index', [
            'niveles' => Sys_Nivel::query()
                ->with(['subniveles.areas.asignaturas'])
                ->orderBy('id')
                ->get(),
            'backUrl' => NavigationItem::hubBackUrlForRoute('sistemas.curriculo'),
            'openNivelId' => $request->integer('nivel') ?: null,
            'openSubnivelId' => $request->integer('subnivel') ?: null,
            'openAreaId' => $request->integer('area') ?: null,
        ]);
    }

    public function storeArea(UpsertCurriculoAreaRequest $request, Sys_Nivel $nivel, Sys_Subnivel $subnivel): RedirectResponse
    {
        $area = $subnivel->areas()->create($request->safe()->only([
            'codigo',
            'nombre',
            'descripcion',
            'orden',
            'aparece_en_libreta',
        ]));

        return $this->redirectToIndex(
            nivel: $nivel,
            subnivel: $subnivel,
            area: $area,
            status: 'area-created',
        );
    }

    public function updateArea(
        UpsertCurriculoAreaRequest $request,
        Sys_Nivel $nivel,
        Sys_Subnivel $subnivel,
        Sys_Area $area,
    ): RedirectResponse {
        $area->update($request->safe()->only([
            'codigo',
            'nombre',
            'descripcion',
            'orden',
            'aparece_en_libreta',
        ]));

        return $this->redirectToIndex(
            nivel: $nivel,
            subnivel: $subnivel,
            area: $area,
            status: 'area-updated',
        );
    }

    public function destroyArea(
        Sys_Nivel $nivel,
        Sys_Subnivel $subnivel,
        Sys_Area $area,
    ): RedirectResponse {
        if ($area->asignaturas()->exists()) {
            return $this->redirectToIndex(
                nivel: $nivel,
                subnivel: $subnivel,
                area: $area,
                error: 'No se puede eliminar el área porque tiene asignaturas asociadas.',
            );
        }

        $area->delete();

        return $this->redirectToIndex(
            nivel: $nivel,
            subnivel: $subnivel,
            status: 'area-deleted',
        );
    }

    public function storeAsignatura(
        UpsertCurriculoAsignaturaRequest $request,
        Sys_Nivel $nivel,
        Sys_Subnivel $subnivel,
        Sys_Area $area,
    ): RedirectResponse {
        $area->asignaturas()->create($request->safe()->only([
            'codigo',
            'nombre',
            'descripcion',
            'orden',
            'horas_semanales',
            'aparece_en_libreta',
        ]));

        return $this->redirectToIndex(
            nivel: $nivel,
            subnivel: $subnivel,
            area: $area,
            status: 'asignatura-created',
        );
    }

    public function updateAsignatura(
        UpsertCurriculoAsignaturaRequest $request,
        Sys_Nivel $nivel,
        Sys_Subnivel $subnivel,
        Sys_Area $area,
        Sys_Asignatura $asignatura,
    ): RedirectResponse {
        $asignatura->update($request->safe()->only([
            'codigo',
            'nombre',
            'descripcion',
            'orden',
            'horas_semanales',
            'aparece_en_libreta',
        ]));

        return $this->redirectToIndex(
            nivel: $nivel,
            subnivel: $subnivel,
            area: $area,
            status: 'asignatura-updated',
        );
    }

    public function destroyAsignatura(
        Sys_Nivel $nivel,
        Sys_Subnivel $subnivel,
        Sys_Area $area,
        Sys_Asignatura $asignatura,
    ): RedirectResponse {
        $asignatura->delete();

        return $this->redirectToIndex(
            nivel: $nivel,
            subnivel: $subnivel,
            area: $area,
            status: 'asignatura-deleted',
        );
    }

    private function redirectToIndex(
        ?Sys_Nivel $nivel = null,
        ?Sys_Subnivel $subnivel = null,
        ?Sys_Area $area = null,
        ?string $status = null,
        ?string $error = null,
    ): RedirectResponse {
        $redirect = redirect()->route('sistemas.curriculo', array_filter([
            'nivel' => $nivel?->id,
            'subnivel' => $subnivel?->id,
            'area' => $area?->id,
        ]));

        if ($status !== null) {
            $redirect->with('status', $status);
        }

        if ($error !== null) {
            $redirect->with('error', $error);
        }

        return $redirect;
    }
}
