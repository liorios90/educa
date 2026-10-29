<?php

namespace App\Http\Controllers;

use App\Auth\ActiveDocenteContexto;
use App\Auth\DocenteContexto;
use App\Enums\TipoContenidoTarea;
use App\Http\Requests\CalificarTareaRequest;
use App\Http\Requests\SelectDocenteContextoRequest;
use App\Http\Requests\StoreTareaRequest;
use App\Http\Requests\UpdateTareaRequest;
use App\Models\EstablecimientoInsumoActividadCalificacion;
use App\Models\EstablecimientoTarea;
use App\Models\EstablecimientoTareaEntregaArchivo;
use App\Models\User;
use App\Services\AsignacionesDocente;
use App\Services\TareasDocente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TareaDocenteController extends Controller
{
    public function __construct(
        private AsignacionesDocente $asignaciones,
        private ActiveDocenteContexto $contexto,
        private TareasDocente $tareas,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $actual = $this->contexto->get($user);

        return view('docente.tareas.index', [
            'aulas' => $this->asignaciones->aulasSelector($user),
            'contexto' => $actual,
            'tareas' => $actual === null ? collect() : $this->tareas->listar($user, $actual),
            'seleccion' => [
                'aula' => old('establecimiento_aula_id', $actual?->aula->id),
                'asignatura' => old('establecimiento_asignatura_id', $actual?->asignatura->id),
                'ciclo' => old('establecimiento_periodo_ciclo_id', $actual?->ciclo->id),
                'parcial' => old('parcial_numero', $actual?->parcialNumero),
            ],
        ]);
    }

    public function storeContexto(SelectDocenteContextoRequest $request): RedirectResponse
    {
        $this->contexto->set($request->contexto());

        return redirect()->route('Docente.tareas.index');
    }

    public function create(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $actual = $this->contexto->get($user);

        if ($actual === null) {
            return redirect()->route('Docente.tareas.index');
        }

        return view('docente.tareas.form', [
            'tarea' => null,
            'contexto' => $actual,
            'alumnos' => $this->opcionesAlumnos($actual),
            'tipos' => TipoContenidoTarea::cases(),
        ]);
    }

    public function store(StoreTareaRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $contexto = $request->contexto();
        $this->contexto->set($contexto);

        $this->tareas->crear(
            $user,
            $contexto,
            (string) $request->validated('nombre'),
            (string) $request->validated('descripcion'),
            (string) $request->validated('fecha_inicio'),
            (string) $request->validated('fecha_fin'),
            $request->tipo(),
            $request->alumnoIds(),
            $request->validated('contenido_texto'),
            $request->validated('contenido_url'),
            $request->archivo(),
        );

        return redirect()
            ->route('Docente.tareas.index')
            ->with('status', 'tarea-enviada');
    }

    public function show(Request $request, EstablecimientoTarea $tarea): View
    {
        $this->autorizar($request, $tarea);

        $tarea->load(['aula', 'asignatura', 'ciclo']);

        return view('docente.tareas.show', [
            'tarea' => $tarea,
        ]);
    }

    public function entregas(Request $request, EstablecimientoTarea $tarea): View
    {
        $this->autorizar($request, $tarea);
        $contexto = $this->contextoDe($request, $tarea);
        $this->contexto->set($contexto);

        $tarea->load([
            'destinatarios.alumno.persona',
            'entregas.alumno.persona',
            'entregas.archivos',
            'actividad.calificaciones',
            'actividad.detalle',
            'aula',
            'asignatura',
            'ciclo',
        ]);

        $notasGuardadas = $tarea->actividad === null
            ? []
            : $tarea->actividad->calificaciones
                ->mapWithKeys(fn (EstablecimientoInsumoActividadCalificacion $calificacion): array => [
                    (int) $calificacion->alumno_id => (string) $calificacion->nota,
                ])
                ->all();

        return view('docente.tareas.entregas', [
            'tarea' => $tarea,
            'contexto' => $contexto,
            'entregas' => $tarea->entregas->keyBy('alumno_id'),
            'insumos' => $this->asignaciones->insumosDe(
                (int) $tarea->establecimiento_aula_id,
                (int) $tarea->establecimiento_asignatura_id,
            ),
            'tipoCalificacion' => $this->asignaciones->tipoCalificacion($contexto),
            'valoresCualitativos' => $this->asignaciones->valoresCualitativos($contexto),
            'notasGuardadas' => $notasGuardadas,
            'yaCalificada' => $tarea->estaCalificada(),
        ]);
    }

    public function edit(Request $request, EstablecimientoTarea $tarea): View
    {
        $this->autorizar($request, $tarea);
        $tarea->load('destinatarios');
        $contexto = $this->contextoDe($request, $tarea);

        return view('docente.tareas.form', [
            'tarea' => $tarea,
            'contexto' => $contexto,
            'alumnos' => $this->opcionesAlumnos($contexto),
            'tipos' => TipoContenidoTarea::cases(),
        ]);
    }

    public function update(UpdateTareaRequest $request, EstablecimientoTarea $tarea): RedirectResponse
    {
        $this->tareas->actualizar(
            $tarea,
            (string) $request->validated('nombre'),
            (string) $request->validated('descripcion'),
            (string) $request->validated('fecha_inicio'),
            (string) $request->validated('fecha_fin'),
            $request->tipo(),
            $request->alumnoIds(),
            $request->validated('contenido_texto'),
            $request->validated('contenido_url'),
            $request->archivo(),
        );

        return redirect()
            ->route('Docente.tareas.show', $tarea)
            ->with('status', 'tarea-actualizada');
    }

    public function destroy(Request $request, EstablecimientoTarea $tarea): RedirectResponse
    {
        $this->autorizar($request, $tarea);
        $this->tareas->eliminar($tarea);

        return redirect()
            ->route('Docente.tareas.index')
            ->with('status', 'tarea-eliminada');
    }

    public function calificar(CalificarTareaRequest $request, EstablecimientoTarea $tarea): RedirectResponse
    {
        $this->tareas->calificarComoInsumo(
            $tarea,
            $request->detalle(),
            $request->empleadoId(),
            $request->notas(),
        );
        $this->contexto->set($request->contexto());

        return redirect()
            ->route('Docente.tareas.entregas', $tarea)
            ->with('status', 'tarea-calificada');
    }

    public function archivo(Request $request, EstablecimientoTarea $tarea): StreamedResponse
    {
        $this->autorizar($request, $tarea);
        abort_if($tarea->contenido_path === null || ! Storage::disk('local')->exists($tarea->contenido_path), 404);

        return Storage::disk('local')->response(
            $tarea->contenido_path,
            $tarea->contenido_nombre ?: 'contenido',
        );
    }

    public function archivoEntrega(
        Request $request,
        EstablecimientoTarea $tarea,
        EstablecimientoTareaEntregaArchivo $archivo,
    ): StreamedResponse {
        $this->autorizar($request, $tarea);
        $archivo->load('entrega');
        abort_if((int) $archivo->entrega?->establecimiento_tarea_id !== (int) $tarea->id, 404);
        abort_if(! Storage::disk('local')->exists($archivo->path), 404);

        if ($archivo->esPdf() || $archivo->esImagen()) {
            return Storage::disk('local')->response($archivo->path, $archivo->nombre_original);
        }

        return Storage::disk('local')->download($archivo->path, $archivo->nombre_original);
    }

    private function autorizar(Request $request, EstablecimientoTarea $tarea): void
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($this->tareas->puedeGestionar($user, $tarea), 404);
    }

    private function contextoDe(Request $request, EstablecimientoTarea $tarea): DocenteContexto
    {
        /** @var User $user */
        $user = $request->user();
        $contexto = $this->asignaciones->contexto(
            $user,
            (int) $tarea->establecimiento_aula_id,
            (int) $tarea->establecimiento_asignatura_id,
            (int) $tarea->establecimiento_periodo_ciclo_id,
            (int) $tarea->parcial_numero,
        );
        abort_if($contexto === null, 404);

        return $contexto;
    }

    /**
     * @return list<array{id: int, etiqueta: string}>
     */
    private function opcionesAlumnos(DocenteContexto $contexto): array
    {
        return $this->tareas->alumnosDelContexto($contexto)
            ->map(function ($alumno): array {
                $nombre = trim(($alumno->persona?->apellidos ?? '').' '.($alumno->persona?->nombres ?? ''));

                return [
                    'id' => (int) $alumno->id,
                    'etiqueta' => $nombre !== '' ? $nombre : 'Alumno '.$alumno->id,
                ];
            })
            ->values()
            ->all();
    }
}
