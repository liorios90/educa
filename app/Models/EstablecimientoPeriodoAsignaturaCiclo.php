<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoPeriodoAsignaturaCiclo extends Model
{
    protected $table = 'establecimiento_periodo_asignatura_ciclos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_periodo_id',
        'establecimiento_asignatura_id',
        'ciclo_orden',
        'porcentaje',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ciclo_orden' => 'integer',
            'porcentaje' => 'decimal:2',
        ];
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
    public function establecimientoAsignatura(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoAsignatura::class, 'establecimiento_asignatura_id');
    }
}
