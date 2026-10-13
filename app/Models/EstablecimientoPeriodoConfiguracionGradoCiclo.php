<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoPeriodoConfiguracionGradoCiclo extends Model
{
    protected $table = 'establecimiento_periodo_configuracion_grado_componentes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'configuracion_grado_id',
        'ciclo_orden',
        'porcentaje_periodo',
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
            'ciclo_orden' => 'integer',
            'porcentaje_periodo' => 'decimal:2',
            'porcentaje_insumos' => 'decimal:2',
            'porcentaje_examen' => 'decimal:2',
            'porcentaje_proyecto' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<EstablecimientoPeriodoConfiguracionGrado, $this>
     */
    public function configuracion(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoPeriodoConfiguracionGrado::class, 'configuracion_grado_id');
    }
}
