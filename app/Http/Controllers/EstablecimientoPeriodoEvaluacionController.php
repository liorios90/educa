<?php

namespace App\Http\Controllers;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\EsquemaCiclo;
use App\Http\Requests\UpdateEstablecimientoPeriodoEvaluacionRequest;
use App\Models\Establecimiento;
use App\Models\EstablecimientoPeriodo;
use App\Services\SyncPeriodoEvaluacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EstablecimientoPeriodoEvaluacionController extends Controller
{
    public function __construct(
        private ActiveOferta $activeOferta,
        private ActivePeriodo $activePeriodo,
    ) {}

    public function edit(Request $request): View|RedirectResponse
    {
        $establecimiento = $this->establecimiento($request);
        $user = $request->user();

        if ($user !== null && $this->activeOferta->needsSelection($user)) {
            return redirect()->guest(route('oferta.select'));
        }

        $oferta = $this->activeOferta->get($user);
        $periodo = $this->activePeriodo->get($user);

        if ($periodo instanceof EstablecimientoPeriodo) {
            $periodo->load('ciclos');
        }

        $examenFinal = old('porcentaje_examen_final', $periodo?->porcentaje_examen_final ?? '0.00');
        $proyectoFinal = old('porcentaje_proyecto_final', $periodo?->porcentaje_proyecto_final ?? '0.00');
        $tieneExamenFinal = old('tiene_examen_final');
        $tieneProyectoFinal = old('tiene_proyecto_final');

        return view('admin.periodo.edit', [
            'establecimiento' => $establecimiento,
            'oferta' => $oferta,
            'periodo' => $periodo,
            'plantillas' => $this->plantillas(),
            'ciclosFormulario' => $this->ciclosFormulario($periodo),
            'esquemaSeleccionado' => old('esquema_ciclo', $periodo?->esquema_ciclo?->value ?? EsquemaCiclo::Quimestres->value),
            'parcialesSeleccionados' => old('numero_parciales', $periodo?->numero_parciales ?? 2),
            'tieneExamenFinal' => $tieneExamenFinal !== null
                ? filter_var($tieneExamenFinal, FILTER_VALIDATE_BOOLEAN)
                : (float) $examenFinal > 0.009,
            'porcentajeExamenFinal' => $examenFinal,
            'tieneProyectoFinal' => $tieneProyectoFinal !== null
                ? filter_var($tieneProyectoFinal, FILTER_VALIDATE_BOOLEAN)
                : (float) $proyectoFinal > 0.009,
            'porcentajeProyectoFinal' => $proyectoFinal,
        ]);
    }

    public function update(
        UpdateEstablecimientoPeriodoEvaluacionRequest $request,
        SyncPeriodoEvaluacion $sync,
    ): RedirectResponse {
        $periodo = $request->periodo();

        abort_if($periodo === null, 403);

        $sync->handle(
            $periodo,
            $request->esquema(),
            $request->integer('numero_parciales'),
            $request->porcentajeExamenFinal(),
            $request->porcentajeProyectoFinal(),
            $request->ciclos(),
        );

        return redirect()
            ->route('Admin.periodo')
            ->with('status', 'periodo-evaluacion-updated');
    }

    private function establecimiento(Request $request): Establecimiento
    {
        $establecimientoId = $request->user()?->establecimiento_id;

        abort_if($establecimientoId === null, 403);

        return Establecimiento::query()->findOrFail($establecimientoId);
    }

    /**
     * @return array<string, list<array{nombre: string, porcentaje: string, porcentaje_insumos: string, tiene_examen: bool, porcentaje_examen: string, tiene_proyecto: bool, porcentaje_proyecto: string}>>
     */
    private function plantillas(): array
    {
        $plantillas = [];

        foreach (EsquemaCiclo::cases() as $esquema) {
            $plantillas[$esquema->value] = $esquema->plantilla();
        }

        return $plantillas;
    }

    /**
     * @return list<array{nombre: string, porcentaje: string, porcentaje_insumos: string, tiene_examen: bool, porcentaje_examen: string, tiene_proyecto: bool, porcentaje_proyecto: string}>
     */
    private function ciclosFormulario(?EstablecimientoPeriodo $periodo): array
    {
        if (is_array(old('ciclos'))) {
            return array_values(array_map(function (array $ciclo): array {
                return [
                    'nombre' => $ciclo['nombre'] ?? '',
                    'porcentaje' => $ciclo['porcentaje'] ?? '0.00',
                    'porcentaje_insumos' => $ciclo['porcentaje_insumos'] ?? '0.00',
                    'tiene_examen' => filter_var($ciclo['tiene_examen'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'porcentaje_examen' => $ciclo['porcentaje_examen'] ?? '0.00',
                    'tiene_proyecto' => filter_var($ciclo['tiene_proyecto'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'porcentaje_proyecto' => $ciclo['porcentaje_proyecto'] ?? '0.00',
                ];
            }, old('ciclos')));
        }

        if ($periodo?->ciclos->isNotEmpty()) {
            return $periodo->ciclos->map(fn ($ciclo): array => [
                'nombre' => $ciclo->nombre,
                'porcentaje' => $ciclo->porcentaje,
                'porcentaje_insumos' => $ciclo->porcentaje_insumos,
                'tiene_examen' => (float) $ciclo->porcentaje_examen > 0.009,
                'porcentaje_examen' => $ciclo->porcentaje_examen,
                'tiene_proyecto' => (float) $ciclo->porcentaje_proyecto > 0.009,
                'porcentaje_proyecto' => $ciclo->porcentaje_proyecto,
            ])->all();
        }

        return ($periodo?->esquema_ciclo ?? EsquemaCiclo::Quimestres)->plantilla();
    }
}
