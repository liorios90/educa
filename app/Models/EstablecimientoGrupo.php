<?php

namespace App\Models;

use Database\Factories\EstablecimientoGrupoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstablecimientoGrupo extends Model
{
    /** @use HasFactory<EstablecimientoGrupoFactory> */
    use HasFactory;

    protected $table = 'establecimiento_grupos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'establecimiento_periodo_id',
        'establecimiento_asignatura_id',
        'empleado_id',
        'nombre',
    ];

    protected static function newFactory(): EstablecimientoGrupoFactory
    {
        return EstablecimientoGrupoFactory::new();
    }

    /**
     * @return BelongsTo<Establecimiento, $this>
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    /**
     * @return BelongsTo<EstablecimientoPeriodo, $this>
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoPeriodo::class, 'establecimiento_periodo_id');
    }

    /**
     * @return BelongsTo<EstablecimientoAsignatura, $this>
     */
    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoAsignatura::class, 'establecimiento_asignatura_id');
    }

    /**
     * @return BelongsTo<Empleado, $this>
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    /**
     * @return HasMany<EstablecimientoGrupoAlumno, $this>
     */
    public function miembros(): HasMany
    {
        return $this->hasMany(EstablecimientoGrupoAlumno::class, 'establecimiento_grupo_id');
    }

    /**
     * @return BelongsToMany<Alumno, $this>
     */
    public function alumnos(): BelongsToMany
    {
        return $this->belongsToMany(Alumno::class, 'establecimiento_grupo_alumnos', 'establecimiento_grupo_id', 'alumno_id')
            ->withTimestamps();
    }
}
