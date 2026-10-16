<?php

namespace App\Models;

use App\Enums\TipoPrograma;
use Database\Factories\EstablecimientoProgramaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstablecimientoPrograma extends Model
{
    /** @use HasFactory<EstablecimientoProgramaFactory> */
    use HasFactory;

    protected $table = 'establecimiento_programas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'establecimiento_modalidad_jornada_id',
        'establecimiento_periodo_id',
        'tipo',
        'nombre',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoPrograma::class,
        ];
    }

    protected static function newFactory(): EstablecimientoProgramaFactory
    {
        return EstablecimientoProgramaFactory::new();
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
     * @return HasMany<EstablecimientoProgramaAlumno, $this>
     */
    public function membresias(): HasMany
    {
        return $this->hasMany(EstablecimientoProgramaAlumno::class, 'establecimiento_programa_id');
    }

    /**
     * @return BelongsToMany<Alumno, $this>
     */
    public function alumnos(): BelongsToMany
    {
        return $this->belongsToMany(Alumno::class, 'establecimiento_programa_alumnos', 'establecimiento_programa_id', 'alumno_id')
            ->withTimestamps();
    }

    /**
     * @return HasMany<EstablecimientoAsignatura, $this>
     */
    public function asignaturas(): HasMany
    {
        return $this->hasMany(EstablecimientoAsignatura::class, 'establecimiento_programa_id');
    }
}
