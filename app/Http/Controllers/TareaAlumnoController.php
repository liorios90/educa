<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTareaEntregaRequest;
use App\Models\Alumno;
use App\Models\EstablecimientoTarea;
use App\Models\EstablecimientoTareaEntrega;
use App\Models\EstablecimientoTareaEntregaArchivo;
use App\Models\User;
use App\Services\LibretaFamiliar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TareaAlumnoController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const MESES = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    public function __construct(private LibretaFamiliar $familiar) {}

    public function index(Request $request): View
    {
        $alumno = $this->alumnoDe($request);
        $mes = $this->mesCalendario($request);
        $inicio = $mes->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $fin = $mes->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $tareas = EstablecimientoTarea::query()
            ->with(['asignatura', 'ciclo', 'aula'])
            ->withExists([
                'entregas as entregada' => fn ($query) => $query->where('alumno_id', $alumno->id),
            ])
            ->with([
                'entregas' => fn ($query) => $query->where('alumno_id', $alumno->id)->with('archivos'),
                'destinatarios' => fn ($query) => $query->where('alumno_id', $alumno->id),
                'actividad.calificaciones' => fn ($query) => $query->where('alumno_id', $alumno->id),
            ])
            ->whereHas('destinatarios', fn ($query) => $query->where('alumno_id', $alumno->id))
            ->whereDate('fecha_inicio', '>=', $inicio->toDateString())
            ->whereDate('fecha_inicio', '<=', $fin->toDateString())
            ->orderBy('fecha_inicio')
            ->orderBy('id')
            ->get();

        return view('alumno.tareas.index', [
            'tareas' => $tareas,
            'mes' => $mes,
            'tituloMes' => self::MESES[$mes->month].' '.$mes->year,
            'mesAnterior' => $mes->copy()->subMonthNoOverflow()->format('Y-m'),
            'mesSiguiente' => $mes->copy()->addMonthNoOverflow()->format('Y-m'),
            'diasSemana' => ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
            'dias' => $this->diasCalendario($inicio, $fin, $mes, $tareas),
        ]);
    }

    public function show(Request $request, EstablecimientoTarea $tarea): View
    {
        $alumno = $this->alumnoAsignado($request, $tarea);
        $tarea->load([
            'asignatura',
            'ciclo',
            'aula',
            'actividad.calificaciones' => fn ($query) => $query->where('alumno_id', $alumno->id),
        ]);
        $entrega = EstablecimientoTareaEntrega::query()
            ->with('archivos')
            ->where('establecimiento_tarea_id', $tarea->id)
            ->where('alumno_id', $alumno->id)
            ->first();
        $destinatario = $tarea->destinatarios()->where('alumno_id', $alumno->id)->first();

        return view('alumno.tareas.show', [
            'tarea' => $tarea,
            'entrega' => $entrega,
            'destinatario' => $destinatario,
            'puedeEntregar' => $tarea->aceptaEntregas(),
        ]);
    }

    public function entregar(StoreTareaEntregaRequest $request, EstablecimientoTarea $tarea): RedirectResponse
    {
        $alumno = $this->alumnoAsignado($request, $tarea);
        abort_unless($tarea->aceptaEntregas(), 404);

        $archivos = $request->file('archivos', []);
        $archivos = is_array($archivos) ? $archivos : [];

        DB::transaction(function () use ($request, $tarea, $alumno, $archivos): void {
            $entrega = EstablecimientoTareaEntrega::query()->updateOrCreate(
                [
                    'establecimiento_tarea_id' => $tarea->id,
                    'alumno_id' => $alumno->id,
                ],
                [
                    'mensaje' => $request->validated('mensaje'),
                ],
            );

            foreach ($archivos as $archivo) {
                if (! $archivo instanceof UploadedFile) {
                    continue;
                }

                $path = $archivo->store('tareas/'.$tarea->id.'/entregas/'.$alumno->id, 'local');
                $entrega->archivos()->create([
                    'path' => $path,
                    'nombre_original' => $archivo->getClientOriginalName(),
                    'mime' => (string) $archivo->getClientMimeType(),
                    'size' => (int) $archivo->getSize(),
                ]);
            }
        });

        return redirect()
            ->route('alumno.tareas.index', [
                'mes' => $tarea->fecha_inicio->format('Y-m'),
                'tarea' => $tarea->id,
            ])
            ->with('status', 'tarea-entregada');
    }

    public function archivo(Request $request, EstablecimientoTarea $tarea): StreamedResponse
    {
        $this->alumnoAsignado($request, $tarea);
        abort_if($tarea->contenido_path === null || ! Storage::disk('local')->exists($tarea->contenido_path), 404);

        return Storage::disk('local')->download(
            $tarea->contenido_path,
            $tarea->contenido_nombre ?: 'contenido',
        );
    }

    public function archivoEntrega(
        Request $request,
        EstablecimientoTarea $tarea,
        EstablecimientoTareaEntregaArchivo $archivo,
    ): StreamedResponse {
        $alumno = $this->alumnoAsignado($request, $tarea);
        $archivo->load('entrega');
        abort_if((int) $archivo->entrega?->establecimiento_tarea_id !== (int) $tarea->id, 404);
        abort_if((int) $archivo->entrega?->alumno_id !== (int) $alumno->id, 404);
        abort_if(! Storage::disk('local')->exists($archivo->path), 404);

        return Storage::disk('local')->download($archivo->path, $archivo->nombre_original);
    }

    private function alumnoDe(Request $request): Alumno
    {
        /** @var User $user */
        $user = $request->user();
        $alumno = $this->familiar->alumnosDe($user)->first();
        abort_if($alumno === null, 404);

        return $alumno;
    }

    private function alumnoAsignado(Request $request, EstablecimientoTarea $tarea): Alumno
    {
        $alumno = $this->alumnoDe($request);
        abort_unless($tarea->destinatarios()->where('alumno_id', $alumno->id)->exists(), 404);

        return $alumno;
    }

    private function mesCalendario(Request $request): Carbon
    {
        $valor = (string) $request->query('mes', now()->format('Y-m'));

        if (preg_match('/^(\d{4})-(\d{2})$/', $valor, $partes) !== 1) {
            return now()->startOfMonth();
        }

        $anio = (int) $partes[1];
        $mes = (int) $partes[2];

        if ($anio < 2000 || $anio > 2100 || $mes < 1 || $mes > 12) {
            return now()->startOfMonth();
        }

        return Carbon::create($anio, $mes, 1)->startOfMonth();
    }

    /**
     * @param  Collection<int, EstablecimientoTarea>  $tareas
     * @return list<array{fecha: string, numero: int, enMes: bool, esHoy: bool, tareas: Collection<int, EstablecimientoTarea>}>
     */
    private function diasCalendario(Carbon $inicio, Carbon $fin, Carbon $mes, Collection $tareas): array
    {
        $porDia = $tareas->groupBy(
            fn (EstablecimientoTarea $tarea): string => $tarea->fecha_inicio->toDateString(),
        );
        $hoy = now()->toDateString();
        $dias = [];
        $cursor = $inicio->copy();

        while ($cursor->lte($fin)) {
            $fecha = $cursor->toDateString();
            $dias[] = [
                'fecha' => $fecha,
                'numero' => $cursor->day,
                'enMes' => $cursor->month === $mes->month,
                'esHoy' => $fecha === $hoy,
                'tareas' => $porDia->get($fecha, collect()),
            ];
            $cursor->addDay();
        }

        return $dias;
    }
}
