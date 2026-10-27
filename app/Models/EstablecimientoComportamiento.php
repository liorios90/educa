<?php

namespace App\Models;

use Database\Factories\EstablecimientoComportamientoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoComportamiento extends Model
{
    /** @use HasFactory<EstablecimientoComportamientoFactory> */
    use HasFactory;

    protected $table = 'establecimiento_comportamientos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'alumno_id',
        'establecimiento_aula_id',
        'establecimiento_periodo_ciclo_id',
        'equivalencia_detalle_id',
        'observacion',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'equivalencia_detalle_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<EquivalenciaDetalle, $this>
     */
    public function detalle(): BelongsTo
    {
        return $this->belongsTo(EquivalenciaDetalle::class, 'equivalencia_detalle_id');
    }

    protected static function newFactory(): EstablecimientoComportamientoFactory
    {
        return EstablecimientoComportamientoFactory::new();
    }

    /**
     * @return BelongsTo<Establecimiento, $this>
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
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
     * @return BelongsTo<EstablecimientoPeriodoCiclo, $this>
     */
    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoPeriodoCiclo::class, 'establecimiento_periodo_ciclo_id');
    }
}
