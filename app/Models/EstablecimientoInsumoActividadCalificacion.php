<?php

namespace App\Models;

use Database\Factories\EstablecimientoInsumoActividadCalificacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoInsumoActividadCalificacion extends Model
{
    /** @use HasFactory<EstablecimientoInsumoActividadCalificacionFactory> */
    use HasFactory;

    protected $table = 'establecimiento_insumo_actividad_calificaciones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_insumo_actividad_id',
        'alumno_id',
        'nota',
    ];

    protected static function newFactory(): EstablecimientoInsumoActividadCalificacionFactory
    {
        return EstablecimientoInsumoActividadCalificacionFactory::new();
    }

    /**
     * @return BelongsTo<EstablecimientoInsumoActividad, $this>
     */
    public function actividad(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoInsumoActividad::class, 'establecimiento_insumo_actividad_id');
    }

    /**
     * @return BelongsTo<Alumno, $this>
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }
}
