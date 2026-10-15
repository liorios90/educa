<?php

namespace App\Http\Controllers\Admin;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHorarioClaseRequest;
use App\Http\Requests\UpdateHorarioClaseRequest;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\HorarioClase;
use App\Services\HorarioClaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HorarioClaseController extends Controller
{
    public function __construct(
        private ActiveOferta $activeOferta,
        private ActivePeriodo $activePeriodo,
    ) {}

    public function show(Request $request, EstablecimientoAula $aula): View
    {
        $this->authorizeAula($request, $aula);
        $aula->load('establecimientoGrado.grado');

        $malla = EstablecimientoAsignatura::query()
            ->with('asignatura')
            ->where('establecimiento_id', $aula->establecimiento_id)
            ->where('establecimiento_modalidad_jornada_id', $aula->establecimiento_modalidad_jornada_id)
            ->where('grado_id', $aula->establecimientoGrado?->grado_id)
            ->get();

        $asignaciones = EstablecimientoAulaDocente::query()
            ->with('empleado.persona')
            ->where('establecimiento_aula_id', $aula->id)
            ->get()
            ->keyBy('establecimiento_asignatura_id');

        $materias = $malla
            ->map(function (EstablecimientoAsignatura $fila) use ($asignaciones): ?array {
                $asignacion = $asignaciones->get($fila->id);

                if ($asignacion === null || $fila->asignatura === null) {
                    return null;
                }

                return [
                    'id' => (int) $fila->id,
                    'nombre' => $fila->asignatura->nombre,
                    'docente' => trim(($asignacion->empleado?->persona?->apellidos ?? '').' '.($asignacion->empleado?->persona?->nombres ?? '')),
                    'docenteId' => (int) $asignacion->empleado_id,
                ];
            })
            ->filter()
            ->sortBy('nombre')
            ->values();

        $horarios = HorarioClase::query()
            ->with('malla.asignatura')
            ->where('establecimiento_aula_id', $aula->id)
            ->orderBy('hora_inicio')
            ->get();
        $horariosPorDia = $horarios->groupBy('dia_semana');

        $horaInicioMinutos = 7 * 60;
        $horaFinMinutos = 18 * 60;

        foreach ($horarios as $horario) {
            [$hora, $minuto] = array_map('intval', explode(':', substr($horario->hora_inicio, 0, 5)));
            $horaInicioMinutos = min($horaInicioMinutos, intdiv($hora * 60 + $minuto, 60) * 60);

            [$hora, $minuto] = array_map('intval', explode(':', substr($horario->hora_fin, 0, 5)));
            $horaFinMinutos = max($horaFinMinutos, (int) (ceil(($hora * 60 + $minuto) / 60) * 60));
        }

        $horaFinMinutos = min($horaFinMinutos, 23 * 60 + 45);

        $docentesPorMalla = $asignaciones->mapWithKeys(fn (EstablecimientoAulaDocente $asignacion): array => [
            $asignacion->establecimiento_asignatura_id => trim(
                ($asignacion->empleado?->persona?->apellidos ?? '').' '.($asignacion->empleado?->persona?->nombres ?? ''),
            ),
        ]);

        $bloqueosDocentes = DB::table('horario_clases as horario')
            ->join('establecimiento_aulas as aula', 'aula.id', '=', 'horario.establecimiento_aula_id')
            ->join('establecimiento_aula_docentes as asignacion', function ($join): void {
                $join->on('asignacion.establecimiento_aula_id', '=', 'horario.establecimiento_aula_id')
                    ->on('asignacion.establecimiento_asignatura_id', '=', 'horario.establecimiento_asignatura_id');
            })
            ->where('aula.establecimiento_periodo_id', $aula->establecimiento_periodo_id)
            ->where('horario.establecimiento_aula_id', '<>', $aula->id)
            ->whereIn('asignacion.empleado_id', $materias->pluck('docenteId')->unique())
            ->get([
                'asignacion.empleado_id as docente_id',
                'horario.dia_semana as dia',
                'horario.hora_inicio as inicio',
                'horario.hora_fin as fin',
            ])
            ->map(fn (object $bloqueo): array => [
                'docenteId' => (int) $bloqueo->docente_id,
                'dia' => (int) $bloqueo->dia,
                'inicio' => substr($bloqueo->inicio, 0, 5),
                'fin' => substr($bloqueo->fin, 0, 5),
            ])
            ->values();

        $horariosVisuales = $horarios->map(fn (HorarioClase $horario): array => [
            'id' => (int) $horario->id,
            'dia' => (int) $horario->dia_semana,
            'inicio' => substr($horario->hora_inicio, 0, 5),
            'fin' => substr($horario->hora_fin, 0, 5),
            'asignaturaId' => (int) $horario->establecimiento_asignatura_id,
            'asignatura' => $horario->malla?->asignatura?->nombre ?? 'Asignatura',
            'docente' => $docentesPorMalla->get($horario->establecimiento_asignatura_id, 'Sin docente asignado'),
        ])->values();

        return view('admin.horarios.show', [
            'aula' => $aula,
            'materias' => $materias,
            'horariosPorDia' => $horariosPorDia,
            'horariosVisuales' => $horariosVisuales,
            'docentesPorMalla' => $docentesPorMalla,
            'bloqueosDocentes' => $bloqueosDocentes,
            'horaInicio' => sprintf('%02d:%02d', intdiv($horaInicioMinutos, 60), $horaInicioMinutos % 60),
            'horaFin' => sprintf('%02d:%02d', intdiv($horaFinMinutos, 60), $horaFinMinutos % 60),
            'dias' => [
                ['numero' => 1, 'nombre' => 'Lunes'],
                ['numero' => 2, 'nombre' => 'Martes'],
                ['numero' => 3, 'nombre' => 'Miércoles'],
                ['numero' => 4, 'nombre' => 'Jueves'],
                ['numero' => 5, 'nombre' => 'Viernes'],
                ['numero' => 6, 'nombre' => 'Sábado'],
                ['numero' => 7, 'nombre' => 'Domingo'],
            ],
        ]);
    }

    public function store(StoreHorarioClaseRequest $request, EstablecimientoAula $aula, HorarioClaseService $service): RedirectResponse
    {
        $cantidad = $service->store($aula, $request->validated()['clases']);

        return redirect()
            ->route('Admin.aulas.horario', $aula)
            ->with('status', 'horario-created')
            ->with('clases_creadas', $cantidad);
    }

    public function update(
        UpdateHorarioClaseRequest $request,
        EstablecimientoAula $aula,
        HorarioClase $horario,
        HorarioClaseService $service,
    ): RedirectResponse|JsonResponse {
        $this->authorizeAula($request, $aula);
        abort_unless((int) $horario->establecimiento_aula_id === (int) $aula->id, 404);

        $service->update($aula, $horario, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Clase movida al nuevo horario.']);
        }

        return redirect()
            ->route('Admin.aulas.horario', $aula)
            ->with('status', 'horario-updated');
    }

    public function destroy(Request $request, EstablecimientoAula $aula, HorarioClase $horario): RedirectResponse|JsonResponse
    {
        $this->authorizeAula($request, $aula);
        abort_unless((int) $horario->establecimiento_aula_id === (int) $aula->id, 404);

        $horario->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Clase eliminada del horario.']);
        }

        return redirect()
            ->route('Admin.aulas.horario', $aula)
            ->with('status', 'horario-deleted');
    }

    private function authorizeAula(Request $request, EstablecimientoAula $aula): void
    {
        $user = $request->user();

        abort_if($user === null, 404);

        $oferta = $this->activeOferta->get($user);
        $periodo = $this->activePeriodo->get($user);

        abort_unless(
            $user !== null
            && $oferta !== null
            && $periodo !== null
            && (int) $aula->establecimiento_id === (int) $user->establecimiento_id
            && (int) $aula->establecimiento_modalidad_jornada_id === (int) $oferta->id
            && (int) $aula->establecimiento_periodo_id === (int) $periodo->id,
            404,
        );
    }
}
