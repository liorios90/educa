<?php

namespace App\Models;

use Database\Factories\EstablecimientoModalidadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstablecimientoModalidad extends Model
{
    /** @use HasFactory<EstablecimientoModalidadFactory> */
    use HasFactory;

    protected $table = 'establecimiento_modalidades';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'modalidad_id',
    ];

    protected static function newFactory(): EstablecimientoModalidadFactory
    {
        return EstablecimientoModalidadFactory::new();
    }

    /**
     * @return BelongsTo<Establecimiento, $this>
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    /**
     * @return BelongsTo<Sys_Modalidad, $this>
     */
    public function modalidad(): BelongsTo
    {
        return $this->belongsTo(Sys_Modalidad::class, 'modalidad_id');
    }

    /**
     * @return HasMany<EstablecimientoModalidadJornada, $this>
     */
    public function establecimientoJornadas(): HasMany
    {
        return $this->hasMany(EstablecimientoModalidadJornada::class);
    }

    /**
     * @return BelongsToMany<Sys_Jornada, $this>
     */
    public function jornadas(): BelongsToMany
    {
        return $this->belongsToMany(
            Sys_Jornada::class,
            'establecimiento_modalidad_jornadas',
            'establecimiento_modalidad_id',
            'jornada_id',
        )->withTimestamps();
    }
}
