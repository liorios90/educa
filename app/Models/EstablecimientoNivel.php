<?php

namespace App\Models;

use Database\Factories\EstablecimientoNivelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoNivel extends Model
{
    /** @use HasFactory<EstablecimientoNivelFactory> */
    use HasFactory;

    protected $table = 'establecimiento_niveles';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'establecimiento_modalidad_jornada_id',
        'nivel_id',
    ];

    protected static function newFactory(): EstablecimientoNivelFactory
    {
        return EstablecimientoNivelFactory::new();
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
     * @return BelongsTo<Sys_Nivel, $this>
     */
    public function nivel(): BelongsTo
    {
        return $this->belongsTo(Sys_Nivel::class, 'nivel_id');
    }
}
