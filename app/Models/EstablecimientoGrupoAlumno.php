<?php

namespace App\Models;

use Database\Factories\EstablecimientoGrupoAlumnoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoGrupoAlumno extends Model
{
    /** @use HasFactory<EstablecimientoGrupoAlumnoFactory> */
    use HasFactory;

    protected $table = 'establecimiento_grupo_alumnos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_grupo_id',
        'establecimiento_periodo_id',
        'establecimiento_asignatura_id',
        'alumno_id',
    ];

    protected static function newFactory(): EstablecimientoGrupoAlumnoFactory
    {
        return EstablecimientoGrupoAlumnoFactory::new();
    }

    /**
     * @return BelongsTo<EstablecimientoGrupo, $this>
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoGrupo::class, 'establecimiento_grupo_id');
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
     * @return BelongsTo<Alumno, $this>
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }
}
