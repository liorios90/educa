<?php

namespace App\Models;

use Database\Factories\EstablecimientoPeriodoCicloFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoPeriodoCiclo extends Model
{
    /** @use HasFactory<EstablecimientoPeriodoCicloFactory> */
    use HasFactory;

    protected $table = 'establecimiento_periodo_ciclos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_periodo_id',
        'orden',
        'nombre',
        'porcentaje',
        'porcentaje_insumos',
        'porcentaje_examen',
        'porcentaje_proyecto',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'porcentaje' => 'decimal:2',
            'porcentaje_insumos' => 'decimal:2',
            'porcentaje_examen' => 'decimal:2',
            'porcentaje_proyecto' => 'decimal:2',
        ];
    }

    protected static function newFactory(): EstablecimientoPeriodoCicloFactory
    {
        return EstablecimientoPeriodoCicloFactory::new();
    }

    /**
     * @return BelongsTo<EstablecimientoPeriodo, $this>
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoPeriodo::class, 'establecimiento_periodo_id');
    }
}
