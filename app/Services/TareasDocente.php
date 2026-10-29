<?php

namespace App\Services;

use App\Auth\DocenteContexto;
use App\Enums\TipoContenidoTarea;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoInsumoActividad;
use App\Models\EstablecimientoInsumoActividadCalificacion;
use App\Models\EstablecimientoTarea;
use App\Models\EstablecimientoTareaDestinatario;
use App\Models\TipoInsumoDetalle;
use App\Models\User;
use App\Support\HtmlSeguro;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TareasDocente
{
    public function __construct(private AsignacionesDocente $asignaciones) {}

    public function empleadoDe(User $user): ?Empleado
    {
        $user->loadMissing('persona.empleados');

        return $user->persona?->empleados->first();
    }

    /**
     * @return Collection<int, EstablecimientoTarea>
     */
    public function listar(User $user, DocenteContexto $contexto): Collection
    {
        $empleado = $this->empleadoDe($user);

        if ($empleado === null) {
            return collect();
        }

        return EstablecimientoTarea::query()
            ->withCount(['destinatarios', 'entregas'])
            ->where('empleado_id', $empleado->id)
            ->where('establecimiento_aula_id', $contexto->aula->id)
            ->where('establecimiento_asignatura_id', $contexto->asignatura->id)
            ->where('establecimiento_periodo_ciclo_id', $contexto->ciclo->id)
            ->where('parcial_numero', $contexto->parcialNumero)
            ->latest('id')
            ->get();
    }

    public function puedeGestionar(User $user, EstablecimientoTarea $tarea): bool
    {
        $empleado = $this->empleadoDe($user);

        if ($empleado === null) {
            return false;
        }

        if ((int) $tarea->empleado_id !== (int) $empleado->id) {
            return false;
        }

        if ((int) $tarea->establecimiento_id !== (int) $user->establecimiento_id) {
            return false;
        }

        return $this->asignaciones->contexto(
            $user,
            (int) $tarea->establecimiento_aula_id,
            (int) $tarea->establecimiento_asignatura_id,
            (int) $tarea->establecimiento_periodo_ciclo_id,
            (int) $tarea->parcial_numero,
        ) !== null;
    }

    /**
     * @return Collection<int, Alumno>
     */
    public function alumnosDelContexto(DocenteContexto $contexto): Collection
    {
        return $this->asignaciones->alumnos($contexto);
    }

    /**
     * @param  list<int>  $alumnoIds
     */
    public function crear(
        User $user,
        DocenteContexto $contexto,
        string $nombre,
        string $descripcion,
        string $fechaInicio,
        string $fechaFin,
        TipoContenidoTarea $tipo,
        array $alumnoIds,
        ?string $contenidoTexto,
        ?string $contenidoUrl,
        ?UploadedFile $archivo,
    ): EstablecimientoTarea {
        $empleadoId = $this->empleadoIdDeAsignacion($user, $contexto);

        return DB::transaction(function () use (
            $user,
            $contexto,
            $nombre,
            $descripcion,
            $fechaInicio,
            $fechaFin,
            $tipo,
            $alumnoIds,
            $contenidoTexto,
            $contenidoUrl,
            $archivo,
            $empleadoId,
        ): EstablecimientoTarea {
            $tarea = EstablecimientoTarea::query()->create([
                'establecimiento_id' => $user->establecimiento_id,
                'empleado_id' => $empleadoId,
                'establecimiento_aula_id' => $contexto->aula->id,
                'establecimiento_asignatura_id' => $contexto->asignatura->id,
                'establecimiento_periodo_ciclo_id' => $contexto->ciclo->id,
                'parcial_numero' => $contexto->parcialNumero,
                'nombre' => $nombre,
                'descripcion' => HtmlSeguro::limpiar($descripcion),
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'contenido_tipo' => $tipo,
                'contenido_texto' => $tipo === TipoContenidoTarea::Texto ? $contenidoTexto : null,
                'contenido_url' => $tipo === TipoContenidoTarea::Url ? $contenidoUrl : null,
            ]);

            $this->guardarContenidoArchivo($tarea, $tipo, $archivo);
            $this->sincronizarDestinatarios($tarea, $alumnoIds);

            return $tarea->refresh();
        });
    }

    /**
     * @param  list<int>  $alumnoIds
     */
    public function actualizar(
        EstablecimientoTarea $tarea,
        string $nombre,
        string $descripcion,
        string $fechaInicio,
        string $fechaFin,
        TipoContenidoTarea $tipo,
        array $alumnoIds,
        ?string $contenidoTexto,
        ?string $contenidoUrl,
        ?UploadedFile $archivo,
    ): EstablecimientoTarea {
        return DB::transaction(function () use (
            $tarea,
            $nombre,
            $descripcion,
            $fechaInicio,
            $fechaFin,
            $tipo,
            $alumnoIds,
            $contenidoTexto,
            $contenidoUrl,
            $archivo,
        ): EstablecimientoTarea {
            $tipoAnterior = $tarea->contenido_tipo;
            $pathAnterior = $tarea->contenido_path;

            $tarea->update([
                'nombre' => $nombre,
                'descripcion' => HtmlSeguro::limpiar($descripcion),
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'contenido_tipo' => $tipo,
                'contenido_texto' => $tipo === TipoContenidoTarea::Texto ? $contenidoTexto : null,
                'contenido_url' => $tipo === TipoContenidoTarea::Url ? $contenidoUrl : null,
            ]);

            if ($tipo->requiereArchivo()) {
                if ($archivo !== null) {
                    $this->borrarArchivo($pathAnterior);
                    $this->guardarContenidoArchivo($tarea, $tipo, $archivo);
                } elseif ($tipoAnterior !== $tipo) {
                    $this->borrarArchivo($pathAnterior);
                    $tarea->update([
                        'contenido_path' => null,
                        'contenido_nombre' => null,
                    ]);
                }
            } else {
                $this->borrarArchivo($pathAnterior);
                $tarea->update([
                    'contenido_path' => null,
                    'contenido_nombre' => null,
                ]);
            }

            $this->sincronizarDestinatarios($tarea, $alumnoIds);

            return $tarea->refresh();
        });
    }

    public function eliminar(EstablecimientoTarea $tarea): void
    {
        DB::transaction(function () use ($tarea): void {
            $tarea->load('entregas.archivos');

            foreach ($tarea->entregas as $entrega) {
                foreach ($entrega->archivos as $archivo) {
                    $this->borrarArchivo($archivo->path);
                }
            }

            $this->borrarArchivo($tarea->contenido_path);
            $tarea->delete();
        });
    }

    /**
     * @param  array<int, string>  $notas
     */
    public function calificarComoInsumo(
        EstablecimientoTarea $tarea,
        TipoInsumoDetalle $detalle,
        int $empleadoId,
        array $notas,
    ): EstablecimientoInsumoActividad {
        return DB::transaction(function () use ($tarea, $detalle, $empleadoId, $notas): EstablecimientoInsumoActividad {
            $actual = EstablecimientoTarea::query()
                ->whereKey($tarea->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($actual->estaCalificada(), 404);

            $actividad = EstablecimientoInsumoActividad::query()->create([
                'establecimiento_aula_id' => $actual->establecimiento_aula_id,
                'establecimiento_asignatura_id' => $actual->establecimiento_asignatura_id,
                'establecimiento_periodo_ciclo_id' => $actual->establecimiento_periodo_ciclo_id,
                'parcial_numero' => $actual->parcial_numero,
                'tipo_insumo_detalle_id' => $detalle->id,
                'empleado_id' => $empleadoId,
                'nombre' => $actual->nombre,
            ]);

            foreach ($notas as $alumnoId => $nota) {
                EstablecimientoInsumoActividadCalificacion::query()->create([
                    'establecimiento_insumo_actividad_id' => $actividad->id,
                    'alumno_id' => $alumnoId,
                    'nota' => $nota,
                ]);
            }

            $actual->update([
                'establecimiento_insumo_actividad_id' => $actividad->id,
            ]);

            return $actividad;
        });
    }

    public function empleadoIdDeAsignacion(User $user, DocenteContexto $contexto): int
    {
        $user->loadMissing('persona.empleados');

        $asignacion = EstablecimientoAulaDocente::query()
            ->where('establecimiento_aula_id', $contexto->aula->id)
            ->where('establecimiento_asignatura_id', $contexto->asignatura->id)
            ->whereIn('empleado_id', $user->persona?->empleados->pluck('id') ?? [])
            ->first();

        abort_if($asignacion === null, 404);

        return (int) $asignacion->empleado_id;
    }

    /**
     * @param  list<int>  $alumnoIds
     */
    private function sincronizarDestinatarios(EstablecimientoTarea $tarea, array $alumnoIds): void
    {
        $actuales = $tarea->destinatarios()->pluck('alumno_id')->map(fn (mixed $id): int => (int) $id)->all();
        $nuevos = array_values(array_unique($alumnoIds));

        $quitar = array_diff($actuales, $nuevos);

        if ($quitar !== []) {
            EstablecimientoTareaDestinatario::query()
                ->where('establecimiento_tarea_id', $tarea->id)
                ->whereIn('alumno_id', $quitar)
                ->delete();
        }

        foreach (array_diff($nuevos, $actuales) as $alumnoId) {
            $tarea->destinatarios()->create(['alumno_id' => $alumnoId]);
        }
    }

    private function guardarContenidoArchivo(EstablecimientoTarea $tarea, TipoContenidoTarea $tipo, ?UploadedFile $archivo): void
    {
        if (! $tipo->requiereArchivo() || $archivo === null) {
            return;
        }

        $path = $archivo->store('tareas/'.$tarea->id.'/contenido', 'local');

        $tarea->update([
            'contenido_path' => $path,
            'contenido_nombre' => $archivo->getClientOriginalName(),
        ]);
    }

    private function borrarArchivo(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk('local')->delete($path);
    }
}
