<?php

namespace App\Models;

use Database\Factories\EstablecimientoInsumoActividadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstablecimientoInsumoActividad extends Model
{
    /** @use HasFactory<EstablecimientoInsumoActividadFactory> */
    use HasFactory;

    protected $table = 'establecimiento_insumo_actividades';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_aula_id',
        'establecimiento_asignatura_id',
        'establecimiento_periodo_ciclo_id',
        'parcial_numero',
        'tipo_insumo_detalle_id',
        'empleado_id',
        'nombre',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parcial_numero' => 'integer',
        ];
    }

    protected static function newFactory(): EstablecimientoInsumoActividadFactory
    {
        return EstablecimientoInsumoActividadFactory::new();
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
     * @return BelongsTo<TipoInsumoDetalle, $this>
     */
    public function detalle(): BelongsTo
    {
        return $this->belongsTo(TipoInsumoDetalle::class, 'tipo_insumo_detalle_id');
    }

    /**
     * @return BelongsTo<Empleado, $this>
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    /**
     * @return HasMany<EstablecimientoInsumoActividadCalificacion, $this>
     */
    public function calificaciones(): HasMany
    {
        return $this->hasMany(EstablecimientoInsumoActividadCalificacion::class, 'establecimiento_insumo_actividad_id');
    }
}
