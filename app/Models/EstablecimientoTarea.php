<?php

namespace App\Models;

use App\Enums\TipoContenidoTarea;
use Carbon\Carbon;
use Database\Factories\EstablecimientoTareaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'establecimiento_id',
    'empleado_id',
    'establecimiento_aula_id',
    'establecimiento_asignatura_id',
    'establecimiento_periodo_ciclo_id',
    'parcial_numero',
    'nombre',
    'descripcion',
    'fecha_inicio',
    'fecha_fin',
    'contenido_tipo',
    'contenido_texto',
    'contenido_url',
    'contenido_path',
    'contenido_nombre',
    'establecimiento_insumo_actividad_id',
])]
class EstablecimientoTarea extends Model
{
    /** @use HasFactory<EstablecimientoTareaFactory> */
    use HasFactory;

    protected $table = 'establecimiento_tareas';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parcial_numero' => 'integer',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'contenido_tipo' => TipoContenidoTarea::class,
        ];
    }

    protected static function newFactory(): EstablecimientoTareaFactory
    {
        return EstablecimientoTareaFactory::new();
    }

    /**
     * @return BelongsTo<Establecimiento, $this>
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    /**
     * @return BelongsTo<Empleado, $this>
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    /**
     * @return BelongsTo<EstablecimientoAula, $this>
     */
    public function aula(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoAula::class, 'establecimiento_aula_id');
    }

    /**
     * @return BelongsTo<EstablecimientoAsignatura, $this>
     */
    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoAsignatura::class, 'establecimiento_asignatura_id');
    }

    /**
     * @return BelongsTo<EstablecimientoPeriodoCiclo, $this>
     */
    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoPeriodoCiclo::class, 'establecimiento_periodo_ciclo_id');
    }

    /**
     * @return BelongsTo<EstablecimientoInsumoActividad, $this>
     */
    public function actividad(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoInsumoActividad::class, 'establecimiento_insumo_actividad_id');
    }

    public function estaCalificada(): bool
    {
        return $this->establecimiento_insumo_actividad_id !== null;
    }

    public function notaDelAlumno(?int $alumnoId): ?string
    {
        if ($alumnoId === null) {
            return null;
        }

        $nota = $this->actividad?->calificaciones->firstWhere('alumno_id', $alumnoId)?->nota;

        if ($nota === null || trim((string) $nota) === '') {
            return null;
        }

        return (string) $nota;
    }

    /**
     * @return HasMany<EstablecimientoTareaDestinatario, $this>
     */
    public function destinatarios(): HasMany
    {
        return $this->hasMany(EstablecimientoTareaDestinatario::class, 'establecimiento_tarea_id');
    }

    /**
     * @return HasMany<EstablecimientoTareaEntrega, $this>
     */
    public function entregas(): HasMany
    {
        return $this->hasMany(EstablecimientoTareaEntrega::class, 'establecimiento_tarea_id');
    }

    public function aceptaEntregas(?Carbon $hoy = null): bool
    {
        $dia = ($hoy ?? now())->toDateString();

        return $dia >= $this->fecha_inicio->toDateString()
            && $dia <= $this->fecha_fin->toDateString();
    }
}
