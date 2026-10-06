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

        DB::transaction(function () use ($aula, $docentes): void {
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

        $asignaturaIds = $malla->pluck('asignatura_id')->unique()->values();
        $docentesPorAsignatura = [];

        if ($asignaturaIds->isNotEmpty()) {
            $docentes = Empleado::query()
                ->with(['persona', 'especialidades'])
                ->where('activo', 1)
                ->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))
                ->whereHas('persona.user', fn ($query) => $query->role(Role::Docente->value))
                ->whereHas('especialidades', fn ($query) => $query->whereIn('sys_asignaturas.id', $asignaturaIds))
                ->get()
                ->sortBy(fn (Empleado $empleado): string => mb_strtolower(trim(
                    ($empleado->persona?->apellidos ?? '').' '.($empleado->persona?->nombres ?? '')
                )).' '.$empleado->id);

            foreach ($docentes as $docente) {
                $nombre = trim(($docente->persona?->apellidos ?? '').' '.($docente->persona?->nombres ?? ''));

                foreach ($docente->especialidades as $asignatura) {
                    if (! $asignaturaIds->contains($asignatura->id)) {
                        continue;
                    }

                    $docentesPorAsignatura[$asignatura->id][] = [
                        'id' => $docente->id,
                        'nombre' => $nombre,
                    ];
                }
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
