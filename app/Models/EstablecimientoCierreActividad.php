<?php

namespace App\Models;

use App\Enums\TipoCierreEvaluacion;
use Database\Factories\EstablecimientoCierreActividadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstablecimientoCierreActividad extends Model
{
    /** @use HasFactory<EstablecimientoCierreActividadFactory> */
    use HasFactory;

    protected $table = 'establecimiento_cierre_actividades';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_aula_id',
        'establecimiento_asignatura_id',
        'establecimiento_periodo_ciclo_id',
        'tipo',
        'empleado_id',
        'nombre',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoCierreEvaluacion::class,
        ];
    }

    protected static function newFactory(): EstablecimientoCierreActividadFactory
    {
        return EstablecimientoCierreActividadFactory::new();
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
     * @return BelongsTo<Empleado, $this>
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    /**
     * @return HasMany<EstablecimientoCierreActividadCalificacion, $this>
     */
    public function calificaciones(): HasMany
    {
        return $this->hasMany(EstablecimientoCierreActividadCalificacion::class, 'establecimiento_cierre_actividad_id');
    }
}
