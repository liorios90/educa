<?php

namespace App\Models;

use Database\Factories\EstablecimientoIndicadorObservacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoIndicadorObservacion extends Model
{
    /** @use HasFactory<EstablecimientoIndicadorObservacionFactory> */
    use HasFactory;

    protected $table = 'establecimiento_indicador_observaciones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'alumno_id',
        'establecimiento_aula_id',
        'establecimiento_asignatura_id',
        'establecimiento_periodo_ciclo_id',
        'indicador_evaluacion_id',
    ];

    protected static function newFactory(): EstablecimientoIndicadorObservacionFactory
    {
        return EstablecimientoIndicadorObservacionFactory::new();
    }

    /**
     * @return BelongsTo<Alumno, $this>
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    /**
     * @return BelongsTo<Sys_IndicadorEvaluacion, $this>
     */
    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Sys_IndicadorEvaluacion::class, 'indicador_evaluacion_id');
    }
}
