<?php

namespace App\Http\Controllers;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\Role;
use App\Http\Requests\StoreEstablecimientoAulaRequest;
use App\Http\Requests\UpdateEstablecimientoAulaDocentesRequest;
use App\Http\Requests\UpdateEstablecimientoAulaRequest;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoModalidadJornada;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EstablecimientoAulaController extends Controller
{
    public function __construct(
        private ActiveOferta $activeOferta,
        private ActivePeriodo $activePeriodo,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $establecimiento = $this->establecimiento($request);
        $user = $request->user();

        if ($user !== null && $this->activeOferta->needsSelection($user)) {
            return redirect()->guest(route('oferta.select'));
        }

        $oferta = $this->activeOferta->get($user);
        $periodo = $this->activePeriodo->get($user);

        $grados = $oferta === null
            ? collect()
            : EstablecimientoGrado::query()
                ->with('grado')
                ->where('establecimiento_id', $establecimiento->id)
                ->where('establecimiento_modalidad_jornada_id', $oferta->id)
                ->orderBy('subnivel_id')
                ->orderBy('grado_id')
                ->orderBy('id')
                ->get();

        $aulas = $periodo === null || $oferta === null
            ? collect()
            : $this->aulasDelPeriodo($establecimiento, $oferta, $periodo->id, $grados);

        $datosDocentes = $periodo === null || $oferta === null
            ? ['docentesPorAsignatura' => [], 'materiasPorGrado' => []]
            : $this->materiasYDocentes($establecimiento, $oferta, $grados);

        return view('admin.aulas.index', [
            'establecimiento' => $establecimiento,
            'oferta' => $oferta,
            'periodo' => $periodo,
            'grados' => $grados,
            'aulas' => $aulas,
            'aulasPorGrado' => $aulas->groupBy(fn (EstablecimientoAula $aula): int => (int) $aula->establecimiento_grado_id),
            'docentesPorAsignatura' => $datosDocentes['docentesPorAsignatura'],
            'materiasPorGrado' => $datosDocentes['materiasPorGrado'],
            'gradosParaDocentes' => $this->gradosParaDocentes($aulas, $datosDocentes['materiasPorGrado']),
        ]);
    }

    public function store(StoreEstablecimientoAulaRequest $request): RedirectResponse
    {
        $establecimiento = $this->establecimiento($request);
        $oferta = $request->oferta();
        $periodo = $request->periodo();

        abort_if($oferta === null || $periodo === null, 403);

        $paralelos = $request->paralelos();
        $gradoId = $request->integer('establecimiento_grado_id');

        DB::transaction(function () use ($establecimiento, $oferta, $periodo, $gradoId, $paralelos): void {
            foreach ($paralelos as $paralelo) {
                $establecimiento->aulas()->create([
                    'establecimiento_modalidad_jornada_id' => $oferta->id,
                    'establecimiento_periodo_id' => $periodo->id,
                    'establecimiento_grado_id' => $gradoId,
                    'paralelo' => $paralelo,
                ]);
            }
        });

        return redirect()
            ->route('Admin.aulas')
            ->with('status', 'aula-created')
            ->with('aulas_creadas', count($paralelos));
    }

    public function update(UpdateEstablecimientoAulaRequest $request, EstablecimientoAula $aula): RedirectResponse
    {
        $aula->update([
            'paralelo' => $request->string('paralelo')->toString(),
        ]);

        return redirect()
            ->route('Admin.aulas')
            ->with('status', 'aula-updated');
    }

    public function updateDocentes(UpdateEstablecimientoAulaDocentesRequest $request, EstablecimientoAula $aula): RedirectResponse
    {
        /** @var array<int, int|null> $docentes */
        $docentes = $request->validated('docentes');
        $aulaIds = $request->aulaIds();

        DB::transaction(function () use ($aulaIds, $docentes): void {
            $aulas = EstablecimientoAula::query()
                ->whereKey($aulaIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            Empleado::query()
                ->whereIn('id', collect($docentes)->filter()->map(fn (mixed $id): int => (int) $id)->unique()->sort()->values())
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            foreach ($aulas as $destino) {
                $this->aplicarDocentes($destino, $docentes, $aulas->count() > 1);
            }
        });

        return redirect()
            ->route('Admin.aulas')
            ->with('status', 'aula-docentes-updated')
            ->with('pestana', 'docentes');
    }

    public function destroy(Request $request, EstablecimientoAula $aula): RedirectResponse
    {
        $establecimiento = $this->establecimiento($request);
        $oferta = $this->activeOferta->get($request->user());
        $periodo = $this->activePeriodo->get($request->user());

        abort_unless(
            (int) $aula->establecimiento_id === (int) $establecimiento->id
            && $oferta !== null
            && $periodo !== null
            && (int) $aula->establecimiento_modalidad_jornada_id === (int) $oferta->id
            && (int) $aula->establecimiento_periodo_id === (int) $periodo->id,
            404,
        );

        $aula->delete();

        return redirect()
            ->route('Admin.aulas')
            ->with('status', 'aula-deleted');
    }

    /**
     * @param  array<int, int|null>  $docentes
     */
    private function aplicarDocentes(EstablecimientoAula $aula, array $docentes, bool $variasAulas): void
    {
        $actuales = DB::table('establecimiento_aula_docentes')
            ->where('establecimiento_aula_id', $aula->id)
            ->get()
            ->keyBy('establecimiento_asignatura_id');
        $materiasConHorario = DB::table('horario_clases')
            ->where('establecimiento_aula_id', $aula->id)
            ->pluck('establecimiento_asignatura_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique();

        foreach ($docentes as $mallaId => $empleadoId) {
            $actual = $actuales->get((int) $mallaId)?->empleado_id;
            $actual = $actual === null ? null : (int) $actual;
            $nuevo = $empleadoId === null ? null : (int) $empleadoId;

            if ($materiasConHorario->contains((int) $mallaId) && $actual !== $nuevo) {
                $mensaje = $variasAulas
                    ? 'Elimina primero las clases programadas de '.$aula->etiqueta().' antes de cambiar su docente.'
                    : 'Elimina primero las clases programadas de una asignatura antes de cambiar su docente.';

                throw ValidationException::withMessages([
                    'docentes' => $mensaje,
                ])->errorBag('aula-docentes');
            }
        }

        $aula->asignaciones()->delete();

        foreach ($docentes as $mallaId => $empleadoId) {
            if ($empleadoId === null) {
                continue;
            }

            $aula->asignaciones()->create([
                'establecimiento_asignatura_id' => (int) $mallaId,
                'empleado_id' => (int) $empleadoId,
            ]);
        }
    }

    /**
     * @param  Collection<int, EstablecimientoAula>  $aulas
     * @param  array<int, list<array{id: int, asignaturaId: int, nombre: string}>>  $materiasPorGrado
     * @return list<array{id: int, nombre: string, materias: list<array{id: int, asignaturaId: int, nombre: string}>, aulas: list<array{id: int, paralelo: string, etiqueta: string, action: string, materias: list<array{id: int, asignaturaId: int, nombre: string, empleadoId: string}>}>}>
     */
    private function gradosParaDocentes(Collection $aulas, array $materiasPorGrado): array
    {
        return $aulas
            ->groupBy(fn (EstablecimientoAula $aula): int => (int) $aula->establecimiento_grado_id)
            ->map(function (Collection $grupo) use ($materiasPorGrado): array {
                /** @var EstablecimientoAula $primera */
                $primera = $grupo->first();
                $gradoId = (int) $primera->establecimiento_grado_id;
                $materias = $materiasPorGrado[$gradoId] ?? [];

                return [
                    'id' => $gradoId,
                    'nombre' => $primera->establecimientoGrado?->etiqueta() ?? 'Grado',
                    'materias' => $materias,
                    'aulas' => $grupo
                        ->map(function (EstablecimientoAula $aula) use ($materias): array {
                            return [
                                'id' => (int) $aula->id,
                                'paralelo' => (string) $aula->paralelo,
                                'etiqueta' => $aula->etiqueta(),
                                'action' => route('Admin.aulas.docentes.update', $aula),
                                'materias' => collect($materias)
                                    ->map(function (array $materia) use ($aula): array {
                                        $asignacion = $aula->asignaciones->firstWhere('establecimiento_asignatura_id', $materia['id']);

                                        return [
                                            ...$materia,
                                            'empleadoId' => $asignacion === null ? '' : (string) $asignacion->empleado_id,
                                        ];
                                    })
                                    ->values()
                                    ->all(),
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, EstablecimientoGrado>  $grados
     * @return Collection<int, EstablecimientoAula>
     */
    private function aulasDelPeriodo(Establecimiento $establecimiento, EstablecimientoModalidadJornada $oferta, int $periodoId, Collection $grados): Collection
    {
        $ordenGrado = $grados->values()->pluck('id')->flip();

        return EstablecimientoAula::query()
            ->with(['establecimientoGrado.grado', 'asignaciones'])
            ->where('establecimiento_id', $establecimiento->id)
            ->where('establecimiento_modalidad_jornada_id', $oferta->id)
            ->where('establecimiento_periodo_id', $periodoId)
            ->get()
            ->sortBy(fn (EstablecimientoAula $aula): string => sprintf(
                '%05d-%s-%05d',
                $ordenGrado[$aula->establecimiento_grado_id] ?? 99999,
                $aula->paralelo,
                $aula->id,
            ))
            ->values();
    }

    /**
     * @param  Collection<int, EstablecimientoGrado>  $grados
     * @return array{
     *     docentesPorAsignatura: array<int, list<array{id: int, nombre: string}>>,
     *     materiasPorGrado: array<int, list<array{id: int, asignaturaId: int, nombre: string}>>
     * }
     */
    private function materiasYDocentes(Establecimiento $establecimiento, EstablecimientoModalidadJornada $oferta, Collection $grados): array
    {
        if ($grados->isEmpty()) {
            return [
                'docentesPorAsignatura' => [],
                'materiasPorGrado' => [],
            ];
        }

        $malla = EstablecimientoAsignatura::query()
            ->with('asignatura.area')
            ->where('establecimiento_id', $establecimiento->id)
            ->where('establecimiento_modalidad_jornada_id', $oferta->id)
            ->whereIn('grado_id', $grados->pluck('grado_id'))
            ->get()
            ->sortBy(fn (EstablecimientoAsignatura $fila): string => sprintf(
                '%05d-%s-%05d-%s-%05d',
                (int) ($fila->asignatura?->area?->orden ?? 0),
                (string) ($fila->asignatura?->area?->nombre ?? ''),
                (int) ($fila->asignatura?->orden ?? 0),
                (string) ($fila->asignatura?->nombre ?? ''),
                $fila->id,
            ))
            ->values();

        $materiasPorGrado = [];

        foreach ($grados as $grado) {
            $materiasPorGrado[$grado->id] = $malla
                ->where('grado_id', $grado->grado_id)
                ->map(fn (EstablecimientoAsignatura $fila): array => [
                    'id' => $fila->id,
                    'asignaturaId' => (int) $fila->asignatura_id,
                    'nombre' => $this->etiquetaAsignatura($fila),
                ])
                ->values()
                ->all();
        }

        $asignaturaIds = $malla->pluck('asignatura_id')->map(fn (mixed $id): int => (int) $id)->unique()->values();
        $docentesPorAsignatura = [];

        if ($asignaturaIds->isNotEmpty()) {
            $docentes = Empleado::query()
                ->with(['persona', 'especialidades'])
                ->where('activo', 1)
                ->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))
                ->whereHas('persona.user', fn ($query) => $query->role(Role::Docente->value))
                ->get()
                ->sortBy(fn (Empleado $empleado): string => mb_strtolower(trim(
                    ($empleado->persona?->apellidos ?? '').' '.($empleado->persona?->nombres ?? '')
                )).' '.$empleado->id);

            $todos = $docentes
                ->map(fn (Empleado $docente): array => [
                    'id' => $docente->id,
                    'nombre' => trim(($docente->persona?->apellidos ?? '').' '.($docente->persona?->nombres ?? '')),
                ])
                ->values()
                ->all();

            foreach ($asignaturaIds as $asignaturaId) {
                $especialistas = $docentes
                    ->filter(fn (Empleado $docente): bool => $docente->especialidades->contains('id', $asignaturaId))
                    ->map(fn (Empleado $docente): array => [
                        'id' => $docente->id,
                        'nombre' => trim(($docente->persona?->apellidos ?? '').' '.($docente->persona?->nombres ?? '')),
                    ])
                    ->values()
                    ->all();

                $docentesPorAsignatura[$asignaturaId] = $especialistas !== [] ? $especialistas : $todos;
            }
        }

        return [
            'docentesPorAsignatura' => $docentesPorAsignatura,
            'materiasPorGrado' => $materiasPorGrado,
        ];
    }

    private function etiquetaAsignatura(EstablecimientoAsignatura $fila): string
    {
        $nombre = $fila->asignatura?->nombre ?? 'Asignatura';
        $area = $fila->asignatura?->area?->nombre;

        if ($area === null || $area === $nombre) {
            return $nombre;
        }

        return $area.' · '.$nombre;
    }

    private function establecimiento(Request $request): Establecimiento
    {
        $establecimientoId = $request->user()?->establecimiento_id;

        abort_if($establecimientoId === null, 403);

        return Establecimiento::query()->findOrFail($establecimientoId);
    }
}
