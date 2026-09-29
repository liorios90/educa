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
    public function index(): View
    {
        return view('sistemas.estructura.index', [
            'niveles' => Sys_Nivel::query()
                ->withCount('subniveles')
                ->orderBy('id')
                ->get(),
            'backUrl' => NavigationItem::hubBackUrlForRoute('sistemas.estructura'),
        ]);
    }

    public function showNivel(Request $request, Sys_Nivel $nivel): View
    {
        $nivel->load(['subniveles.grados']);

        $openGradosId = $request->integer('grados') ?: null;

        if (
            $openGradosId !== null
            && ! $nivel->subniveles->contains(fn (Sys_Subnivel $subnivel): bool => (int) $subnivel->id === $openGradosId)
        ) {
            $openGradosId = null;
        }

        return view('sistemas.estructura.subniveles', [
            'nivel' => $nivel,
            'openGradosId' => $openGradosId,
        ]);
    }

    public function showSubnivel(Sys_Nivel $nivel, Sys_Subnivel $subnivel): RedirectResponse
    {
        return redirect()->route('sistemas.estructura.niveles.show', [
            'nivel' => $nivel,
            'grados' => $subnivel->id,
        ]);
    }

    public function storeNivel(UpsertEstructuraNivelRequest $request): RedirectResponse
    {
        $nivel = Sys_Nivel::query()->create($request->safe()->only(['nombre', 'siglas', 'descripcion']));

        return $this->redirectToSubniveles($nivel, status: 'nivel-created');
    }

    public function updateNivel(UpsertEstructuraNivelRequest $request, Sys_Nivel $nivel): RedirectResponse
    {
        $nivel->update($request->safe()->only(['nombre', 'siglas', 'descripcion']));

        return $this->redirectToNiveles(status: 'nivel-updated');
    }

    public function destroyNivel(Sys_Nivel $nivel): RedirectResponse
    {
        if ($nivel->subniveles()->exists()) {
            return $this->redirectToNiveles(error: 'No se puede eliminar el nivel porque tiene subniveles asociados.');
        }

        if ($nivel->establecimientos()->exists()) {
            return $this->redirectToNiveles(error: 'No se puede eliminar el nivel porque está en uso por un establecimiento.');
        }

        $nivel->delete();

        return $this->redirectToNiveles(status: 'nivel-deleted');
    }

    public function storeSubnivel(UpsertEstructuraSubnivelRequest $request, Sys_Nivel $nivel): RedirectResponse
    {
        $subnivel = $nivel->subniveles()->create($request->safe()->only(['nombre', 'siglas', 'descripcion', 'tipo_calificacion']));

        return $this->redirectToSubniveles($nivel, status: 'subnivel-created', gradosDe: $subnivel);
    }

    public function updateSubnivel(UpsertEstructuraSubnivelRequest $request, Sys_Nivel $nivel, Sys_Subnivel $subnivel): RedirectResponse
    {
        $subnivel->update($request->safe()->only(['nombre', 'siglas', 'descripcion', 'tipo_calificacion']));

        return $this->redirectToSubniveles($nivel, status: 'subnivel-updated');
    }

    public function destroySubnivel(Sys_Nivel $nivel, Sys_Subnivel $subnivel): RedirectResponse
    {
        if ($subnivel->grados()->exists()) {
            return $this->redirectToSubniveles($nivel, error: 'No se puede eliminar el subnivel porque tiene grados asociados.');
        }

        if ($subnivel->areas()->exists()) {
            return $this->redirectToSubniveles($nivel, error: 'No se puede eliminar el subnivel porque tiene áreas asociadas.');
        }

        if ($subnivel->establecimientos()->exists()) {
            return $this->redirectToSubniveles($nivel, error: 'No se puede eliminar el subnivel porque está en uso por un establecimiento.');
        }

        $subnivel->delete();

        return $this->redirectToSubniveles($nivel, status: 'subnivel-deleted');
    }

    public function storeGrado(UpsertEstructuraGradoRequest $request, Sys_Nivel $nivel, Sys_Subnivel $subnivel): RedirectResponse
    {
        $subnivel->grados()->create($request->safe()->only(['nombre', 'siglas', 'descripcion']));

        return $this->redirectToSubniveles($nivel, status: 'grado-created', gradosDe: $subnivel);
    }

    public function updateGrado(UpsertEstructuraGradoRequest $request, Sys_Nivel $nivel, Sys_Subnivel $subnivel, Sys_Grado $grado): RedirectResponse
    {
        $grado->update($request->safe()->only(['nombre', 'siglas', 'descripcion']));

        return $this->redirectToSubniveles($nivel, status: 'grado-updated', gradosDe: $subnivel);
    }

    public function destroyGrado(Sys_Nivel $nivel, Sys_Subnivel $subnivel, Sys_Grado $grado): RedirectResponse
    {
        if ($grado->establecimientos()->exists()) {
            return $this->redirectToSubniveles(
                $nivel,
                error: 'No se puede eliminar el grado porque está en uso por un establecimiento.',
                gradosDe: $subnivel,
            );
        }

        $grado->delete();

        return $this->redirectToSubniveles($nivel, status: 'grado-deleted', gradosDe: $subnivel);
    }

    private function redirectToNiveles(?string $status = null, ?string $error = null): RedirectResponse
    {
        return $this->withFlash(redirect()->route('sistemas.estructura'), $status, $error);
    }

    private function redirectToSubniveles(
        Sys_Nivel $nivel,
        ?string $status = null,
        ?string $error = null,
        ?Sys_Subnivel $gradosDe = null,
    ): RedirectResponse {
        return $this->withFlash(
            redirect()->route('sistemas.estructura.niveles.show', array_filter([
                'nivel' => $nivel,
                'grados' => $gradosDe?->id,
            ])),
            $status,
            $error,
        );
    }

    private function withFlash(RedirectResponse $redirect, ?string $status, ?string $error): RedirectResponse
    {
        if ($status !== null) {
            $redirect->with('status', $status);
        }

        if ($error !== null) {
            $redirect->with('error', $error);
        }

        return $redirect;
    }
}
