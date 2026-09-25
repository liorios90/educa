<?php

namespace App\Models;

use Database\Factories\EstablecimientoSubnivelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoSubnivel extends Model
{
    /** @use HasFactory<EstablecimientoSubnivelFactory> */
    use HasFactory;

    protected $table = 'establecimiento_subniveles';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'establecimiento_modalidad_jornada_id',
        'nivel_id',
        'subnivel_id',
        'nombre',
    ];

    protected static function newFactory(): EstablecimientoSubnivelFactory
    {
        return EstablecimientoSubnivelFactory::new();
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

    /**
     * @return BelongsTo<Sys_Subnivel, $this>
     */
    public function subnivel(): BelongsTo
    {
        return $this->belongsTo(Sys_Subnivel::class, 'subnivel_id');
    }
}
