<?php

namespace App\Models;

use Database\Factories\EstablecimientoAulaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoAula extends Model
{
    /** @use HasFactory<EstablecimientoAulaFactory> */
    use HasFactory;

    protected $table = 'establecimiento_aulas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'establecimiento_modalidad_jornada_id',
        'establecimiento_periodo_id',
        'establecimiento_grado_id',
        'paralelo',
    ];

    protected static function newFactory(): EstablecimientoAulaFactory
    {
        return EstablecimientoAulaFactory::new();
    }

    /**
     * @return BelongsTo<Establecimiento, $this>
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    /**
     * @return BelongsTo<EstablecimientoModalidadJornada, $this>
     */
    public function oferta(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoModalidadJornada::class, 'establecimiento_modalidad_jornada_id');
    }

    /**
     * @return BelongsTo<EstablecimientoPeriodo, $this>
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoPeriodo::class, 'establecimiento_periodo_id');
    }

    /**
     * @return BelongsTo<EstablecimientoGrado, $this>
     */
    public function establecimientoGrado(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoGrado::class, 'establecimiento_grado_id');
    }

    public function etiqueta(): string
    {
        $this->loadMissing('establecimientoGrado.grado');

        return trim($this->establecimientoGrado?->etiqueta().' '.$this->paralelo);
    }
}
