<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertEstructuraGradoRequest;
use App\Http\Requests\UpsertEstructuraNivelRequest;
use App\Http\Requests\UpsertEstructuraSubnivelRequest;
use App\Models\NavigationItem;
use App\Models\Sys_Grado;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EstructuraController extends Controller
{
    public function index(Request $request): View
    {
        return view('sistemas.estructura.index', [
            'niveles' => Sys_Nivel::query()
                ->with(['subniveles.grados'])
                ->orderBy('id')
                ->get(),
            'backUrl' => NavigationItem::hubBackUrlForRoute('sistemas.estructura'),
            'openNivelId' => $request->integer('nivel') ?: null,
            'openSubnivelId' => $request->integer('subnivel') ?: null,
        ]);
    }

    public function storeNivel(UpsertEstructuraNivelRequest $request): RedirectResponse
    {
        $nivel = Sys_Nivel::query()->create($request->safe()->only(['nombre', 'siglas', 'descripcion']));

        return $this->redirectToIndex(nivel: $nivel, status: 'nivel-created');
    }

    public function updateNivel(UpsertEstructuraNivelRequest $request, Sys_Nivel $nivel): RedirectResponse
    {
        $nivel->update($request->safe()->only(['nombre', 'siglas', 'descripcion']));

        return $this->redirectToIndex(nivel: $nivel, status: 'nivel-updated');
    }

    public function destroyNivel(Sys_Nivel $nivel): RedirectResponse
    {
        if ($nivel->subniveles()->exists()) {
            return $this->redirectToIndex(
                nivel: $nivel,
                error: 'No se puede eliminar el nivel porque tiene subniveles asociados.',
            );
        }

        if ($nivel->establecimientos()->exists()) {
            return $this->redirectToIndex(
                nivel: $nivel,
                error: 'No se puede eliminar el nivel porque está en uso por un establecimiento.',
            );
        }

        $nivel->delete();

        return $this->redirectToIndex(status: 'nivel-deleted');
    }

    public function storeSubnivel(UpsertEstructuraSubnivelRequest $request, Sys_Nivel $nivel): RedirectResponse
    {
        $subnivel = $nivel->subniveles()->create($request->safe()->only(['nombre', 'siglas', 'descripcion', 'tipo_calificacion']));

        return $this->redirectToIndex(nivel: $nivel, subnivel: $subnivel, status: 'subnivel-created');
    }

    public function updateSubnivel(UpsertEstructuraSubnivelRequest $request, Sys_Nivel $nivel, Sys_Subnivel $subnivel): RedirectResponse
    {
        $subnivel->update($request->safe()->only(['nombre', 'siglas', 'descripcion', 'tipo_calificacion']));

        return $this->redirectToIndex(nivel: $nivel, subnivel: $subnivel, status: 'subnivel-updated');
    }

    public function destroySubnivel(Sys_Nivel $nivel, Sys_Subnivel $subnivel): RedirectResponse
    {
        if ($subnivel->grados()->exists()) {
            return $this->redirectToIndex(
                nivel: $nivel,
                subnivel: $subnivel,
                error: 'No se puede eliminar el subnivel porque tiene grados asociados.',
            );
        }

        if ($subnivel->areas()->exists()) {
            return $this->redirectToIndex(
                nivel: $nivel,
                subnivel: $subnivel,
                error: 'No se puede eliminar el subnivel porque tiene áreas asociadas.',
            );
        }

        if ($subnivel->establecimientos()->exists()) {
            return $this->redirectToIndex(
                nivel: $nivel,
                subnivel: $subnivel,
                error: 'No se puede eliminar el subnivel porque está en uso por un establecimiento.',
            );
        }

        $subnivel->delete();

        return $this->redirectToIndex(nivel: $nivel, status: 'subnivel-deleted');
    }

    public function storeGrado(UpsertEstructuraGradoRequest $request, Sys_Nivel $nivel, Sys_Subnivel $subnivel): RedirectResponse
    {
        $subnivel->grados()->create($request->safe()->only(['nombre', 'siglas', 'descripcion']));

        return $this->redirectToIndex(nivel: $nivel, subnivel: $subnivel, status: 'grado-created');
    }

    public function updateGrado(UpsertEstructuraGradoRequest $request, Sys_Nivel $nivel, Sys_Subnivel $subnivel, Sys_Grado $grado): RedirectResponse
    {
        $grado->update($request->safe()->only(['nombre', 'siglas', 'descripcion']));

        return $this->redirectToIndex(nivel: $nivel, subnivel: $subnivel, status: 'grado-updated');
    }

    public function destroyGrado(Sys_Nivel $nivel, Sys_Subnivel $subnivel, Sys_Grado $grado): RedirectResponse
    {
        if ($grado->establecimientos()->exists()) {
            return $this->redirectToIndex(
                nivel: $nivel,
                subnivel: $subnivel,
                error: 'No se puede eliminar el grado porque está en uso por un establecimiento.',
            );
        }

        $grado->delete();

        return $this->redirectToIndex(nivel: $nivel, subnivel: $subnivel, status: 'grado-deleted');
    }

    private function redirectToIndex(
        ?Sys_Nivel $nivel = null,
        ?Sys_Subnivel $subnivel = null,
        ?string $status = null,
        ?string $error = null,
    ): RedirectResponse {
        $redirect = redirect()->route('sistemas.estructura', array_filter([
            'nivel' => $nivel?->id,
            'subnivel' => $subnivel?->id,
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
