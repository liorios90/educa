<?php

namespace App\Models;

use Database\Factories\EstablecimientoProgramaAlumnoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoProgramaAlumno extends Model
{
    /** @use HasFactory<EstablecimientoProgramaAlumnoFactory> */
    use HasFactory;

    protected $table = 'establecimiento_programa_alumnos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_programa_id',
        'alumno_id',
    ];

    protected static function newFactory(): EstablecimientoProgramaAlumnoFactory
    {
        return EstablecimientoProgramaAlumnoFactory::new();
    }

    /**
     * @return BelongsTo<EstablecimientoPrograma, $this>
     */
    public function programa(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoPrograma::class, 'establecimiento_programa_id');
    }

    /**
     * @return BelongsTo<Alumno, $this>
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }
}
