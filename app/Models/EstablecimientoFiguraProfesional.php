<?php

namespace App\Models;

use Database\Factories\EstablecimientoFiguraProfesionalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstablecimientoFiguraProfesional extends Model
{
    /** @use HasFactory<EstablecimientoFiguraProfesionalFactory> */
    use HasFactory;

    protected $table = 'establecimiento_figuras_profesionales';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'establecimiento_modalidad_jornada_id',
        'nombre',
    ];

    protected static function newFactory(): EstablecimientoFiguraProfesionalFactory
    {
        return EstablecimientoFiguraProfesionalFactory::new();
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
     * @return HasMany<EstablecimientoAsignaturaPropia, $this>
     */
    public function asignaturas(): HasMany
    {
        return $this->hasMany(EstablecimientoAsignaturaPropia::class, 'figura_profesional_id');
    }

    /**
     * @return HasMany<EstablecimientoAsignatura, $this>
     */
    public function asignaturasDeMalla(): HasMany
    {
        return $this->hasMany(EstablecimientoAsignatura::class, 'figura_profesional_id');
    }

    /**
     * @return HasMany<EstablecimientoMatricula, $this>
     */
    public function matriculas(): HasMany
    {
        return $this->hasMany(EstablecimientoMatricula::class, 'figura_profesional_id');
    }
}
