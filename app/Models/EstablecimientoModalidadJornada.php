<?php

namespace App\Models;

use Database\Factories\EstablecimientoModalidadJornadaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EstablecimientoModalidadJornada extends Model
{
    /** @use HasFactory<EstablecimientoModalidadJornadaFactory> */
    use HasFactory;

    protected $table = 'establecimiento_modalidad_jornadas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_modalidad_id',
        'jornada_id',
    ];

    protected static function newFactory(): EstablecimientoModalidadJornadaFactory
    {
        return EstablecimientoModalidadJornadaFactory::new();
    }

    /**
     * @return BelongsTo<EstablecimientoModalidad, $this>
     */
    public function establecimientoModalidad(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoModalidad::class);
    }

    /**
     * @return BelongsTo<Sys_Jornada, $this>
     */
    public function jornada(): BelongsTo
    {
        return $this->belongsTo(Sys_Jornada::class, 'jornada_id');
    }

    /**
     * @return BelongsToMany<Sys_Nivel, $this>
     */
    public function niveles(): BelongsToMany
    {
        return $this->belongsToMany(
            Sys_Nivel::class,
            'establecimiento_niveles',
            'establecimiento_modalidad_jornada_id',
            'nivel_id',
        )->withPivot('establecimiento_id')->withTimestamps();
    }

    /**
     * @return BelongsToMany<Sys_Subnivel, $this>
     */
    public function subniveles(): BelongsToMany
    {
        return $this->belongsToMany(
            Sys_Subnivel::class,
            'establecimiento_subniveles',
            'establecimiento_modalidad_jornada_id',
            'subnivel_id',
        )->withPivot('establecimiento_id', 'nivel_id', 'nombre')->withTimestamps();
    }

    /**
     * @return BelongsToMany<Sys_Grado, $this>
     */
    public function grados(): BelongsToMany
    {
        return $this->belongsToMany(
            Sys_Grado::class,
            'establecimiento_grados',
            'establecimiento_modalidad_jornada_id',
            'grado_id',
        )->withPivot('establecimiento_id', 'subnivel_id', 'nombre')->withTimestamps();
    }

    public function etiqueta(): string
    {
        $this->loadMissing(['jornada', 'establecimientoModalidad.modalidad']);

        return trim(
            ($this->establecimientoModalidad?->modalidad?->nombre ?? '').
            ' · '.
            ($this->jornada?->nombre ?? ''),
            ' ·',
        );
    }
}
