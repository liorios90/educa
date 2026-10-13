<?php

namespace App\Models;

use Database\Factories\EstablecimientoAulaAsignaturaCicloFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoAulaAsignaturaCiclo extends Model
{
    /** @use HasFactory<EstablecimientoAulaAsignaturaCicloFactory> */
    use HasFactory;

    protected $table = 'establecimiento_aula_asignatura_ciclos';

    /** @var list<string> */
    protected $fillable = [
        'establecimiento_periodo_id',
        'establecimiento_aula_id',
        'establecimiento_asignatura_id',
        'ciclo_orden',
        'porcentaje_insumos',
        'porcentaje_examen',
        'porcentaje_proyecto',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'ciclo_orden' => 'integer',
            'porcentaje_insumos' => 'decimal:2',
            'porcentaje_examen' => 'decimal:2',
            'porcentaje_proyecto' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<EstablecimientoPeriodo, $this> */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoPeriodo::class, 'establecimiento_periodo_id');
    }

    /** @return BelongsTo<EstablecimientoAula, $this> */
    public function aula(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoAula::class, 'establecimiento_aula_id');
    }

    /** @return BelongsTo<EstablecimientoAsignatura, $this> */
    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoAsignatura::class, 'establecimiento_asignatura_id');
    }
}
