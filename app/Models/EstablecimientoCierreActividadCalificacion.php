<?php

namespace App\Models;

use Database\Factories\EstablecimientoCierreActividadCalificacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EstablecimientoCierreActividadCalificacion extends Model
{
    /** @use HasFactory<EstablecimientoCierreActividadCalificacionFactory> */
    use HasFactory;

    protected $table = 'establecimiento_cierre_actividad_calificaciones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_cierre_actividad_id',
        'alumno_id',
        'nota',
    ];

    protected static function newFactory(): EstablecimientoCierreActividadCalificacionFactory
    {
        return EstablecimientoCierreActividadCalificacionFactory::new();
    }

    /**
     * @return BelongsTo<EstablecimientoCierreActividad, $this>
     */
    public function actividad(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoCierreActividad::class, 'establecimiento_cierre_actividad_id');
    }

    /**
     * @return BelongsTo<Alumno, $this>
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    /** @return HasOne<EstablecimientoMejoraCalificacion, $this> */
    public function mejora(): HasOne
    {
        return $this->hasOne(EstablecimientoMejoraCalificacion::class, 'establecimiento_cierre_actividad_calificacion_id');
    }
}
