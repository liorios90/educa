<?php

namespace App\Models;

use Database\Factories\EstablecimientoPeriodoCicloParcialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoPeriodoCicloParcial extends Model
{
    /** @use HasFactory<EstablecimientoPeriodoCicloParcialFactory> */
    use HasFactory;

    protected $table = 'establecimiento_periodo_ciclo_parciales';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_periodo_ciclo_id',
        'numero',
        'cerrado_en',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'cerrado_en' => 'datetime',
        ];
    }

    protected static function newFactory(): EstablecimientoPeriodoCicloParcialFactory
    {
        return EstablecimientoPeriodoCicloParcialFactory::new();
    }

    /**
     * @return BelongsTo<EstablecimientoPeriodoCiclo, $this>
     */
    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoPeriodoCiclo::class, 'establecimiento_periodo_ciclo_id');
    }
}
