<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoMejoraCalificacion extends Model
{
    protected $table = 'establecimiento_mejoras_calificacion';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_cierre_actividad_calificacion_id',
        'tipo_mejora',
        'nota_inicial',
        'nota_refuerzo',
        'nota_adicional',
        'nota_final',
        'fecha_notificacion',
        'fecha_solicitud',
        'fecha_evaluacion',
        'referencia_solicitud',
        'normativa_aplicada',
        'registrado_por',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nota_inicial' => 'decimal:2',
            'nota_refuerzo' => 'decimal:2',
            'nota_adicional' => 'decimal:2',
            'nota_final' => 'decimal:2',
            'fecha_notificacion' => 'date',
            'fecha_solicitud' => 'date',
            'fecha_evaluacion' => 'date',
        ];
    }

    /** @return BelongsTo<EstablecimientoCierreActividadCalificacion, $this> */
    public function calificacion(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoCierreActividadCalificacion::class, 'establecimiento_cierre_actividad_calificacion_id');
    }

    /** @return BelongsTo<User, $this> */
    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
