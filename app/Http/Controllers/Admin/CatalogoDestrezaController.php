<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertCurriculoCriterioRequest;
use App\Http\Requests\UpsertCurriculoDestrezaRequest;
use App\Http\Requests\UpsertCurriculoIndicadorRequest;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\Sys_Asignatura;
use App\Models\Sys_CriterioEvaluacion;
use App\Models\Sys_Destreza;
use App\Models\Sys_IndicadorEvaluacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CatalogoDestrezaController extends Controller
{
    public function storeCriterio(
        UpsertCurriculoCriterioRequest $request,
        EstablecimientoModalidadJornada $oferta,
        Sys_Asignatura $asignatura,
    ): RedirectResponse {
        $this->catalogoDeLaOferta($request, $oferta, $asignatura);
        $asignatura->criterios()->create($request->safe()->only([
            'grado_id',
            'codigo',
            'descripcion',
            'orden',
        ]));

        return $this->volver($oferta, $asignatura, $request->integer('grado_id'), 'criterio-created');
    }

    public function updateCriterio(
        UpsertCurriculoCriterioRequest $request,
        EstablecimientoModalidadJornada $oferta,
        Sys_Asignatura $asignatura,
        Sys_CriterioEvaluacion $criterio,
    ): RedirectResponse {
        $this->catalogoDeLaOferta($request, $oferta, $asignatura);
        abort_unless((int) $criterio->asignatura_id === (int) $asignatura->id, 404);
        $criterio->update($request->safe()->only([
            'grado_id',
            'codigo',
            'descripcion',
            'orden',
        ]));

        return $this->volver($oferta, $asignatura, $request->integer('grado_id'), 'criterio-updated');
    }

    public function destroyCriterio(
        Request $request,
        EstablecimientoModalidadJornada $oferta,
        Sys_Asignatura $asignatura,
        Sys_CriterioEvaluacion $criterio,
    ): RedirectResponse {
        $this->catalogoDeLaOferta($request, $oferta, $asignatura);
        abort_unless((int) $criterio->asignatura_id === (int) $asignatura->id, 404);
        $gradoId = (int) $criterio->grado_id;
        $criterio->delete();

        return $this->volver($oferta, $asignatura, $gradoId, 'criterio-deleted');
    }

    public function storeDestreza(
        UpsertCurriculoDestrezaRequest $request,
        EstablecimientoModalidadJornada $oferta,
        Sys_Asignatura $asignatura,
    ): RedirectResponse {
        $this->catalogoDeLaOferta($request, $oferta, $asignatura);
        $asignatura->destrezas()->create($request->safe()->only([
            'grado_id',
            'criterio_evaluacion_id',
            'codigo',
            'descripcion',
            'imprescindible',
            'orden',
        ]));

        return $this->volver($oferta, $asignatura, $request->integer('grado_id'), 'destreza-created');
    }

    public function updateDestreza(
        UpsertCurriculoDestrezaRequest $request,
        EstablecimientoModalidadJornada $oferta,
        Sys_Asignatura $asignatura,
        Sys_Destreza $destreza,
    ): RedirectResponse {
        $this->catalogoDeLaOferta($request, $oferta, $asignatura);
        $this->asegurarDestreza($asignatura, $destreza);
        $destreza->update($request->safe()->only([
            'grado_id',
            'criterio_evaluacion_id',
            'codigo',
            'descripcion',
            'imprescindible',
            'orden',
        ]));

        return $this->volver($oferta, $asignatura, $request->integer('grado_id'), 'destreza-updated');
    }

    public function destroyDestreza(
        Request $request,
        EstablecimientoModalidadJornada $oferta,
        Sys_Asignatura $asignatura,
        Sys_Destreza $destreza,
    ): RedirectResponse {
        $this->catalogoDeLaOferta($request, $oferta, $asignatura);
        $this->asegurarDestreza($asignatura, $destreza);
        $gradoId = (int) $destreza->grado_id;

        try {
            $destreza->delete();
        } catch (ValidationException $exception) {
            return $this->volver($oferta, $asignatura, $gradoId, error: $exception->validator->errors()->first('destreza'));
        }

        return $this->volver($oferta, $asignatura, $gradoId, 'destreza-deleted');
    }

    public function storeIndicador(
        UpsertCurriculoIndicadorRequest $request,
        EstablecimientoModalidadJornada $oferta,
        Sys_Asignatura $asignatura,
        Sys_Destreza $destreza,
    ): RedirectResponse {
        $this->catalogoDeLaOferta($request, $oferta, $asignatura);
        $this->asegurarDestreza($asignatura, $destreza);
        $destreza->indicadores()->create($request->safe()->only([
            'codigo',
            'descripcion',
            'orden',
        ]));

        return $this->volver($oferta, $asignatura, $request->integer('grado'), 'indicador-created');
    }

    public function updateIndicador(
        UpsertCurriculoIndicadorRequest $request,
        EstablecimientoModalidadJornada $oferta,
        Sys_Asignatura $asignatura,
        Sys_Destreza $destreza,
        Sys_IndicadorEvaluacion $indicador,
    ): RedirectResponse {
        $this->catalogoDeLaOferta($request, $oferta, $asignatura);
        $this->asegurarDestreza($asignatura, $destreza);
        $this->asegurarIndicador($destreza, $indicador);
        $indicador->update($request->safe()->only([
            'codigo',
            'descripcion',
            'orden',
        ]));

        return $this->volver($oferta, $asignatura, $request->integer('grado'), 'indicador-updated');
    }

    public function destroyIndicador(
        Request $request,
        EstablecimientoModalidadJornada $oferta,
        Sys_Asignatura $asignatura,
        Sys_Destreza $destreza,
        Sys_IndicadorEvaluacion $indicador,
    ): RedirectResponse {
        $this->catalogoDeLaOferta($request, $oferta, $asignatura);
        $this->asegurarDestreza($asignatura, $destreza);
        $this->asegurarIndicador($destreza, $indicador);

        try {
            $indicador->delete();
        } catch (ValidationException $exception) {
            return $this->volver($oferta, $asignatura, (int) $destreza->grado_id, error: $exception->validator->errors()->first('indicador'));
        }

        return $this->volver($oferta, $asignatura, (int) $destreza->grado_id, 'indicador-deleted');
    }

    private function catalogoDeLaOferta(
        Request $request,
        EstablecimientoModalidadJornada $oferta,
        Sys_Asignatura $asignatura,
    ): void {
        $establecimientoId = $request->user()?->establecimiento_id;
        abort_if($establecimientoId === null, 403);

        $oferta->loadMissing('establecimientoModalidad');
        abort_unless(
            (int) $oferta->establecimientoModalidad?->establecimiento_id === (int) $establecimientoId,
            404,
        );

        $asignatura->loadMissing('area.subnivel');
        $subnivel = $asignatura->area?->subnivel;
        abort_unless($subnivel !== null && $subnivel->califica_por_destrezas, 404);
        abort_unless(
            $oferta->grados()->where('sys_grados.subnivel_id', $subnivel->id)->exists(),
            404,
        );
    }

    private function asegurarDestreza(Sys_Asignatura $asignatura, Sys_Destreza $destreza): void
    {
        abort_unless((int) $destreza->asignatura_id === (int) $asignatura->id, 404);
    }

    private function asegurarIndicador(Sys_Destreza $destreza, Sys_IndicadorEvaluacion $indicador): void
    {
        abort_unless((int) $indicador->destreza_id === (int) $destreza->id, 404);
    }

    private function volver(
        EstablecimientoModalidadJornada $oferta,
        Sys_Asignatura $asignatura,
        int $gradoId,
        ?string $status = null,
        ?string $error = null,
    ): RedirectResponse {
        $redirect = redirect()->route('Admin.asignaturas.edit', [
            'oferta' => $oferta,
            'area' => $asignatura->area_id,
            'destrezas' => $asignatura->id.'-'.$gradoId,
        ]);

        if ($status !== null) {
            $redirect->with('status', $status);
        }

        if ($error !== null) {
            $redirect->with('error', $error);
        }

        return $redirect;
    }
}
