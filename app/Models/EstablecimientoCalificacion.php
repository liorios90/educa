<?php

namespace App\Models;

use Database\Factories\EstablecimientoCalificacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoCalificacion extends Model
{
    /** @use HasFactory<EstablecimientoCalificacionFactory> */
    use HasFactory;

    protected $table = 'establecimiento_calificaciones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_periodo_id',
        'establecimiento_asignatura_id',
        'alumno_id',
        'ciclo_orden',
        'parcial_numero',
        'nota',
        'establecimiento_grupo_id',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'parcial_numero' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ciclo_orden' => 'integer',
            'parcial_numero' => 'integer',
            'nota' => 'decimal:2',
        ];
    }

    protected static function newFactory(): EstablecimientoCalificacionFactory
    {
        return EstablecimientoCalificacionFactory::new();
    }

    /**
     * @return BelongsTo<EstablecimientoPeriodo, $this>
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoPeriodo::class, 'establecimiento_periodo_id');
    }

    /**
     * @return BelongsTo<EstablecimientoAsignatura, $this>
     */
    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoAsignatura::class, 'establecimiento_asignatura_id');
    }

    /**
     * @return BelongsTo<Alumno, $this>
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    /**
     * @return BelongsTo<EstablecimientoGrupo, $this>
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoGrupo::class, 'establecimiento_grupo_id');
    }
}
