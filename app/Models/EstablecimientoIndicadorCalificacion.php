<?php

namespace App\Models;

use App\Enums\CalificacionDestreza;
use Database\Factories\EstablecimientoIndicadorCalificacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoIndicadorCalificacion extends Model
{
    /** @use HasFactory<EstablecimientoIndicadorCalificacionFactory> */
    use HasFactory;

    protected $table = 'establecimiento_indicador_calificaciones';

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
        'calificacion',
        'observacion',
    ];

    protected static function newFactory(): EstablecimientoIndicadorCalificacionFactory
    {
        return EstablecimientoIndicadorCalificacionFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'calificacion' => CalificacionDestreza::class,
        ];
    }

    /**
     * @return BelongsTo<Alumno, $this>
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
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
     * @return BelongsTo<Sys_IndicadorEvaluacion, $this>
     */
    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Sys_IndicadorEvaluacion::class, 'indicador_evaluacion_id');
    }
}
