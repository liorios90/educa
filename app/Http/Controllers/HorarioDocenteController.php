<?php

namespace App\Http\Controllers;

use App\Models\EstablecimientoAulaDocente;
use App\Models\HorarioClase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HorarioDocenteController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('persona.empleados');

        $empleadoIds = $user->persona?->empleados->pluck('id')->all() ?? [];
        $asignaciones = EstablecimientoAulaDocente::query()
            ->with(['empleado.persona', 'malla.asignatura', 'malla.asignaturaPropia', 'aula.establecimientoGrado.grado'])
            ->whereIn('empleado_id', $empleadoIds)
            ->whereHas('aula', fn ($query) => $query
                ->where('establecimiento_id', $user->establecimiento_id)
                ->whereHas('periodo', fn ($periodo) => $periodo->activo()))
            ->get()
            ->filter(fn (EstablecimientoAulaDocente $asignacion): bool =>
                $asignacion->malla?->asignatura !== null || $asignacion->malla?->asignaturaPropia !== null
            )
            ->values();

        $asignacionesPorAulaAsignatura = $asignaciones->keyBy(fn (EstablecimientoAulaDocente $asignacion): string =>
            $asignacion->establecimiento_aula_id.':'.$asignacion->establecimiento_asignatura_id
        );

        $horarios = HorarioClase::query()
            ->with(['malla.asignatura', 'malla.asignaturaPropia', 'aula.establecimientoGrado.grado'])
            ->whereIn('establecimiento_aula_id', $asignaciones->pluck('establecimiento_aula_id')->unique())
            ->whereIn('establecimiento_asignatura_id', $asignaciones->pluck('establecimiento_asignatura_id')->unique())
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get()
            ->filter(fn (HorarioClase $horario): bool => $asignacionesPorAulaAsignatura->has(
                $horario->establecimiento_aula_id.':'.$horario->establecimiento_asignatura_id,
            ))
            ->map(function (HorarioClase $horario) use ($asignacionesPorAulaAsignatura): array {
                $asignacion = $asignacionesPorAulaAsignatura->get($horario->establecimiento_aula_id.':'.$horario->establecimiento_asignatura_id);

                return [
                    'dia' => (int) $horario->dia_semana,
                    'inicio' => substr($horario->hora_inicio, 0, 5),
                    'fin' => substr($horario->hora_fin, 0, 5),
                    'asignatura' => $horario->malla?->asignaturaPropia?->nombre ?? $horario->malla?->asignatura?->nombre ?? 'Asignatura',
                    'aula' => $horario->aula?->etiqueta() ?? 'Aula',
                    'docente' => trim(($asignacion->empleado?->persona?->apellidos ?? '').' '.($asignacion->empleado?->persona?->nombres ?? '')),
                ];
            })
            ->values();

        $asignaturasYAulas = $asignaciones->map(fn (EstablecimientoAulaDocente $asignacion): array => [
            'asignatura' => $asignacion->malla->asignaturaPropia?->nombre ?? $asignacion->malla->asignatura?->nombre ?? 'Asignatura',
            'aula' => $asignacion->aula?->etiqueta() ?? 'Aula',
        ])->values();

        return view('docente-horarios', [
            'horariosDocente' => $horarios,
            'asignaturasYAulasDocente' => $asignaturasYAulas,
        ]);
    }
}
