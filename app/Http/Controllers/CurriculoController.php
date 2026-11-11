<?php

namespace App\Http\Controllers;

use App\Enums\TipoCalificacion;
use App\Http\Requests\UpdateCurriculoOrganizacionRequest;
use App\Http\Requests\UpsertCurriculoAreaRequest;
use App\Http\Requests\UpsertCurriculoAsignaturaRequest;
use App\Http\Requests\UpsertCurriculoCriterioRequest;
use App\Http\Requests\UpsertCurriculoDestrezaRequest;
use App\Http\Requests\UpsertCurriculoIndicadorRequest;
use App\Models\NavigationItem;
use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use App\Models\Sys_CriterioEvaluacion;
use App\Models\Sys_Destreza;
use App\Models\Sys_IndicadorEvaluacion;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CurriculoController extends Controller
{
    public function index(Request $request): View
    {
        return view('sistemas.curriculo.index', [
            'niveles' => Sys_Nivel::query()
                ->with([
                    'subniveles.grados',
                    'subniveles.areas.asignaturas.criterios',
                    'subniveles.areas.asignaturas.destrezas.criterio',
                    'subniveles.areas.asignaturas.destrezas.indicadores',
                    'subniveles.areas.asignaturas.destrezas.grado',
                ])
                ->orderBy('id')
                ->get(),
            'backUrl' => NavigationItem::hubBackUrlForRoute('sistemas.curriculo'),
            'openNivelId' => $request->integer('nivel') ?: null,
            'openSubnivelId' => $request->integer('subnivel') ?: null,
            'openAreaId' => $request->integer('area') ?: null,
        ]);
    }

    public function updateOrganizacion(
        UpdateCurriculoOrganizacionRequest $request,
        Sys_Nivel $nivel,
        Sys_Subnivel $subnivel,
    ): RedirectResponse {
        $porAmbitos = $subnivel->tipo_calificacion === TipoCalificacion::Cualitativa
            && $request->boolean('usa_ambitos');

        $subnivel->update([
            'usa_ambitos' => $porAmbitos,
            'califica_por_destrezas' => $porAmbitos ? true : $subnivel->califica_por_destrezas,
        ]);

        return $this->redirectToIndex(
            nivel: $nivel,
            subnivel: $subnivel,
            status: 'organizacion-updated',
        );
    }

    public function storeArea(UpsertCurriculoAreaRequest $request, Sys_Nivel $nivel, Sys_Subnivel $subnivel): RedirectResponse
    {
        $area = $subnivel->areas()->create($request->safe()->only([
            'codigo',
            'nombre',
            'descripcion',
            'orden',
            'aparece_en_libreta',
            'es_tecnica',
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
            'es_tecnica',
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

    public function storeCriterio(
        UpsertCurriculoCriterioRequest $request,
        Sys_Nivel $nivel,
        Sys_Subnivel $subnivel,
        Sys_Area $area,
        Sys_Asignatura $asignatura,
    ): RedirectResponse {
        $asignatura->criterios()->create($request->safe()->only([
            'grado_id',
            'codigo',
            'descripcion',
            'orden',
        ]));

        return $this->redirectToIndex(
            nivel: $nivel,
            subnivel: $subnivel,
            area: $area,
            status: 'criterio-created',
        );
    }

    public function updateCriterio(
        UpsertCurriculoCriterioRequest $request,
        Sys_Nivel $nivel,
        Sys_Subnivel $subnivel,
        Sys_Area $area,
        Sys_Asignatura $asignatura,
        Sys_CriterioEvaluacion $criterio,
    ): RedirectResponse {
        abort_unless((int) $criterio->asignatura_id === (int) $asignatura->id, 404);
        $criterio->update($request->safe()->only([
            'grado_id',
            'codigo',
            'descripcion',
            'orden',
        ]));

        return $this->redirectToIndex(
            nivel: $nivel,
            subnivel: $subnivel,
            area: $area,
            status: 'criterio-updated',
        );
    }

    public function destroyCriterio(
        Sys_Nivel $nivel,
        Sys_Subnivel $subnivel,
        Sys_Area $area,
        Sys_Asignatura $asignatura,
        Sys_CriterioEvaluacion $criterio,
    ): RedirectResponse {
        abort_unless((int) $criterio->asignatura_id === (int) $asignatura->id, 404);
        $criterio->delete();

        return $this->redirectToIndex(
            nivel: $nivel,
            subnivel: $subnivel,
            area: $area,
            status: 'criterio-deleted',
        );
    }

    public function storeDestreza(
        UpsertCurriculoDestrezaRequest $request,
        Sys_Nivel $nivel,
        Sys_Subnivel $subnivel,
        Sys_Area $area,
        Sys_Asignatura $asignatura,
    ): RedirectResponse {
        $asignatura->destrezas()->create($request->safe()->only([
            'grado_id',
            'criterio_evaluacion_id',
            'codigo',
            'descripcion',
            'imprescindible',
            'orden',
        ]));

        return $this->redirectToIndex(
            nivel: $nivel,
            subnivel: $subnivel,
            area: $area,
            status: 'destreza-created',
        );
    }

    public function updateDestreza(
        UpsertCurriculoDestrezaRequest $request,
        Sys_Nivel $nivel,
        Sys_Subnivel $subnivel,
        Sys_Area $area,
        Sys_Asignatura $asignatura,
        Sys_Destreza $destreza,
    ): RedirectResponse {
        $destreza->update($request->safe()->only([
            'grado_id',
            'criterio_evaluacion_id',
            'codigo',
            'descripcion',
            'imprescindible',
            'orden',
        ]));

        return $this->redirectToIndex(
            nivel: $nivel,
            subnivel: $subnivel,
            area: $area,
            status: 'destreza-updated',
        );
    }

    public function destroyDestreza(
        Sys_Nivel $nivel,
        Sys_Subnivel $subnivel,
        Sys_Area $area,
        Sys_Asignatura $asignatura,
        Sys_Destreza $destreza,
    ): RedirectResponse {
        try {
            $destreza->delete();
        } catch (ValidationException $exception) {
            return $this->redirectToIndex(
                nivel: $nivel,
                subnivel: $subnivel,
                area: $area,
                error: $exception->validator->errors()->first('destreza'),
            );
        }

        return $this->redirectToIndex(
            nivel: $nivel,
            subnivel: $subnivel,
            area: $area,
            status: 'destreza-deleted',
        );
    }

    public function storeIndicador(
        UpsertCurriculoIndicadorRequest $request,
        Sys_Nivel $nivel,
        Sys_Subnivel $subnivel,
        Sys_Area $area,
        Sys_Asignatura $asignatura,
        Sys_Destreza $destreza,
    ): RedirectResponse {
        $destreza->indicadores()->create($request->safe()->only([
            'codigo',
            'descripcion',
            'orden',
        ]));

        return $this->redirectToIndex(
            nivel: $nivel,
            subnivel: $subnivel,
            area: $area,
            status: 'indicador-created',
        );
    }

    public function updateIndicador(
        UpsertCurriculoIndicadorRequest $request,
        Sys_Nivel $nivel,
        Sys_Subnivel $subnivel,
        Sys_Area $area,
        Sys_Asignatura $asignatura,
        Sys_Destreza $destreza,
        Sys_IndicadorEvaluacion $indicador,
    ): RedirectResponse {
        $indicador->update($request->safe()->only([
            'codigo',
            'descripcion',
            'orden',
        ]));

        return $this->redirectToIndex(
            nivel: $nivel,
            subnivel: $subnivel,
            area: $area,
            status: 'indicador-updated',
        );
    }

    public function destroyIndicador(
        Sys_Nivel $nivel,
        Sys_Subnivel $subnivel,
        Sys_Area $area,
        Sys_Asignatura $asignatura,
        Sys_Destreza $destreza,
        Sys_IndicadorEvaluacion $indicador,
    ): RedirectResponse {
        try {
            $indicador->delete();
        } catch (ValidationException $exception) {
            return $this->redirectToIndex(
                nivel: $nivel,
                subnivel: $subnivel,
                area: $area,
                error: $exception->validator->errors()->first('indicador'),
            );
        }

        return $this->redirectToIndex(
            nivel: $nivel,
            subnivel: $subnivel,
            area: $area,
            status: 'indicador-deleted',
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
