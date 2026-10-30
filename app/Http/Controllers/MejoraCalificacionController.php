<?php

namespace App\Http\Controllers;

use App\Auth\ActiveDocenteContexto;
use App\Auth\DocenteContexto;
use App\Enums\TipoCierreEvaluacion;
use App\Http\Requests\StoreMejoraCalificacionRequest;
use App\Models\EstablecimientoCierreActividadCalificacion;
use App\Models\User;
use App\Services\AsignacionesDocente;
use App\Services\MejoraCalificacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MejoraCalificacionController extends Controller
{
    public function __construct(
        private AsignacionesDocente $asignaciones,
        private ActiveDocenteContexto $contexto,
        private MejoraCalificacion $mejoraCalificacion,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $actual = $this->contexto->get($user);
        $contexto = $actual === null ? null : $this->asignaciones->contexto(
            $user,
            (int) $actual->aula->id,
            (int) $actual->asignatura->id,
            (int) $actual->ciclo->id,
            0,
        );

        $calificaciones = collect();
        $normativa = null;
        $requiereNotaRefuerzo = false;

        if ($contexto !== null) {
            $contexto->aula->loadMissing(['establecimiento', 'periodo']);
            $normativa = $this->mejoraCalificacion->normativa($contexto->aula->establecimiento, $contexto->aula->periodo);
            $requiereNotaRefuerzo = $normativa === '2024';
            $calificaciones = EstablecimientoCierreActividadCalificacion::query()
                ->with(['alumno.persona', 'actividad'])
                ->whereHas('actividad', function ($query) use ($contexto): void {
                    $query->where('establecimiento_aula_id', $contexto->aula->id)
                        ->where('establecimiento_asignatura_id', $contexto->asignatura->id)
                        ->where(function ($query) use ($contexto): void {
                            $query->where('establecimiento_periodo_ciclo_id', $contexto->ciclo->id)
                                ->orWhereIn('tipo', [TipoCierreEvaluacion::ExamenFinal->value, TipoCierreEvaluacion::ProyectoFinal->value]);
                        });
                })
                ->with('mejora')
                ->orderBy('alumno_id')
                ->get()
                ->map(function (EstablecimientoCierreActividadCalificacion $calificacion): EstablecimientoCierreActividadCalificacion {
                    $calificacion->setAttribute('tipo_mejora_disponible', $this->mejoraCalificacion->tipo((float) $calificacion->nota));

                    return $calificacion;
                });
        }

        return view('docente.mejora-calificaciones', [
            'aulas' => $this->asignaciones->aulasSelector($user),
            'contexto' => $contexto,
            'calificaciones' => $calificaciones,
            'normativa' => $normativa,
            'requiereNotaRefuerzo' => $requiereNotaRefuerzo,
        ]);
    }

    public function store(StoreMejoraCalificacionRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $actual = $this->contexto->get($user);

        if ($actual === null) {
            abort(404);
        }

        $contexto = $this->asignaciones->contexto(
            $user,
            (int) $actual->aula->id,
            (int) $actual->asignatura->id,
            (int) $actual->ciclo->id,
            0,
        );

        if ($contexto === null) {
            abort(404);
        }

        if (! $contexto->permiteIngresarCalificaciones()) {
            throw ValidationException::withMessages([
                'calificacion_id' => DocenteContexto::MENSAJE_PERIODO_CERRADO,
            ]);
        }

        $calificacion = EstablecimientoCierreActividadCalificacion::query()
            ->whereKey($request->integer('calificacion_id'))
            ->whereHas('actividad', function ($query) use ($contexto): void {
                $query->where('establecimiento_aula_id', $contexto->aula->id)
                    ->where('establecimiento_asignatura_id', $contexto->asignatura->id)
                    ->where(function ($query) use ($contexto): void {
                        $query->where('establecimiento_periodo_ciclo_id', $contexto->ciclo->id)
                            ->orWhereIn('tipo', [TipoCierreEvaluacion::ExamenFinal->value, TipoCierreEvaluacion::ProyectoFinal->value]);
                    });
            })
            ->firstOrFail();

        $datos = $request->validated();
        $this->mejoraCalificacion->registrar($calificacion, $user, [
            'nota_refuerzo' => isset($datos['nota_refuerzo']) ? (float) $datos['nota_refuerzo'] : null,
            'nota_adicional' => (float) $datos['nota_adicional'],
            'fecha_notificacion' => $datos['fecha_notificacion'],
            'fecha_solicitud' => $datos['fecha_solicitud'],
            'fecha_evaluacion' => $datos['fecha_evaluacion'],
            'referencia_solicitud' => $datos['referencia_solicitud'],
        ]);

        return redirect()
            ->route('Docente.mejora-calificaciones.index')
            ->with('status', 'mejora-calificacion-registrada');
    }
}
