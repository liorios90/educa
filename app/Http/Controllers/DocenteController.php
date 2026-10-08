<?php

namespace App\Http\Controllers;

use App\Auth\ActiveOferta;
use App\Enums\Role;
use App\Http\Requests\UpdateDocenteEspecialidadRequest;
use App\Models\Empleado;
use App\Models\EstablecimientoAsignatura;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocenteController extends Controller
{
    public function __construct(private ActiveOferta $activeOferta) {}

    public function index(Request $request): View
    {
        $establecimientoId = $this->establecimientoId($request);

        return view('admin.docentes.index', [
            'docentes' => Empleado::query()
                ->select('empleados.*')
                ->with(['persona', 'especialidades.area.subnivel'])
                ->join('personas', 'personas.id', '=', 'empleados.persona_id')
                ->where('personas.establecimiento_id', $establecimientoId)
                ->whereHas('persona.user', fn ($query) => $query->role(Role::Docente->value))
                ->orderBy('personas.apellidos')
                ->orderBy('personas.nombres')
                ->orderBy('empleados.id')
                ->get(),
        ]);
    }

    public function areas(Request $request, Empleado $empleado): View
    {
        $empleado = $this->docenteForAdmin($request, $empleado);

        return view('admin.docentes.areas', [
            'docente' => $empleado,
            'subniveles' => $this->areasPorSubnivel($request),
        ]);
    }

    public function update(UpdateDocenteEspecialidadRequest $request, Empleado $empleado): RedirectResponse
    {
        $empleado = $this->docenteForAdmin($request, $empleado);

        $empleado->especialidades()->sync($request->asignaturaIds());

        return redirect()
            ->route('Admin.docentes')
            ->with('status', 'docente-especialidad-updated');
    }

    private function docenteForAdmin(Request $request, Empleado $empleado): Empleado
    {
        $empleado->loadMissing(['persona.user.roles', 'especialidades']);

        abort_unless(
            (int) $empleado->persona?->establecimiento_id === $this->establecimientoId($request)
            && $empleado->persona?->user?->hasRole(Role::Docente) === true,
            404,
        );

        return $empleado;
    }

    /**
     * @return list<array{
     *     id: int,
     *     nombre: string,
     *     areas: list<array{id: int, nombre: string}>
     * }>
     */
    private function areasPorSubnivel(Request $request): array
    {
        $oferta = $this->activeOferta->get($request->user());

        if ($oferta === null) {
            return [];
        }

        /** @var array<int, array{id: int, nombre: string, areas: array<int, array{id: int, nombre: string, orden: int}>}> $subniveles */
        $subniveles = [];

        EstablecimientoAsignatura::query()
            ->with('asignatura.area.subnivel')
            ->where('establecimiento_id', $this->establecimientoId($request))
            ->where('establecimiento_modalidad_jornada_id', $oferta->id)
            ->orderBy('asignatura_id')
            ->get()
            ->unique('asignatura_id')
            ->each(function (EstablecimientoAsignatura $fila) use (&$subniveles): void {
                $area = $fila->asignatura?->area;

                if ($area === null) {
                    return;
                }

                $subnivelId = (int) ($area->subnivel?->id ?? 0);

                if (! isset($subniveles[$subnivelId])) {
                    $subniveles[$subnivelId] = [
                        'id' => $subnivelId,
                        'nombre' => $area->subnivel?->nombre ?? 'Sin subnivel',
                        'areas' => [],
                    ];
                }

                $subniveles[$subnivelId]['areas'][$area->id] = [
                    'id' => (int) $area->id,
                    'nombre' => $area->nombre,
                    'orden' => (int) $area->orden,
                ];
            });

        return collect($subniveles)
            ->sortBy([
                ['id', 'asc'],
                ['nombre', 'asc'],
            ])
            ->map(function (array $subnivel): array {
                $areas = collect($subnivel['areas'])
                    ->sortBy([
                        ['orden', 'asc'],
                        ['nombre', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->map(fn (array $area): array => [
                        'id' => $area['id'],
                        'nombre' => $area['nombre'],
                    ])
                    ->values()
                    ->all();

                return [
                    'id' => $subnivel['id'],
                    'nombre' => $subnivel['nombre'],
                    'areas' => $areas,
                ];
            })
            ->values()
            ->all();
    }

    private function establecimientoId(Request $request): int
    {
        $establecimientoId = $request->user()?->establecimiento_id;

        abort_if($establecimientoId === null, 403);

        return (int) $establecimientoId;
    }
}
