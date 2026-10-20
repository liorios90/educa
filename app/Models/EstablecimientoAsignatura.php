<?php

namespace App\Models;

use App\Enums\EscalaCalificacion;
use App\Enums\TipoAsignatura;
use App\Enums\TipoCalificacion;
use Database\Factories\EstablecimientoAsignaturaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstablecimientoAsignatura extends Model
{
    /** @use HasFactory<EstablecimientoAsignaturaFactory> */
    use HasFactory;

    protected $table = 'establecimiento_asignaturas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'establecimiento_modalidad_jornada_id',
        'grado_id',
        'asignatura_id',
        'asignatura_propia_id',
        'figura_profesional_id',
        'aparece_en_libreta',
        'horas_semanales',
        'tipo_calificacion',
        'tipo_asignatura',
        'es_flotante',
        'escala',
        'establecimiento_programa_id',
        'califica_por_destrezas',
        'usa_tabla_equivalencias',
        'porcentaje',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'aparece_en_libreta' => true,
        'tipo_asignatura' => TipoAsignatura::TroncoComun,
        'es_flotante' => false,
        'escala' => EscalaCalificacion::Nacional,
        'califica_por_destrezas' => false,
        'usa_tabla_equivalencias' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aparece_en_libreta' => 'boolean',
            'horas_semanales' => 'integer',
            'tipo_calificacion' => TipoCalificacion::class,
            'tipo_asignatura' => TipoAsignatura::class,
            'es_flotante' => 'boolean',
            'escala' => EscalaCalificacion::class,
            'califica_por_destrezas' => 'boolean',
            'usa_tabla_equivalencias' => 'boolean',
            'porcentaje' => 'decimal:2',
        ];
    }

    protected static function newFactory(): EstablecimientoAsignaturaFactory
    {
        return EstablecimientoAsignaturaFactory::new();
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
     * @return BelongsTo<Sys_Grado, $this>
     */
    public function grado(): BelongsTo
    {
        return $this->belongsTo(Sys_Grado::class, 'grado_id');
    }

    /**
     * @return BelongsTo<Sys_Asignatura, $this>
     */
    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Sys_Asignatura::class, 'asignatura_id');
    }

    /**
     * @return BelongsTo<EstablecimientoAsignaturaPropia, $this>
     */
    public function asignaturaPropia(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoAsignaturaPropia::class, 'asignatura_propia_id');
    }

    /**
     * @return BelongsTo<EstablecimientoFiguraProfesional, $this>
     */
    public function figuraProfesional(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoFiguraProfesional::class, 'figura_profesional_id');
    }

    /**
     * @return BelongsTo<EstablecimientoPrograma, $this>
     */
    public function programa(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoPrograma::class, 'establecimiento_programa_id');
    }

    /**
     * @return HasMany<EstablecimientoGrupo, $this>
     */
    public function grupos(): HasMany
    {
        return $this->hasMany(EstablecimientoGrupo::class, 'establecimiento_asignatura_id');
    }

    /**
     * @return HasMany<EstablecimientoAlumnoAsignatura, $this>
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(EstablecimientoAlumnoAsignatura::class, 'establecimiento_asignatura_id');
    }

    public function nombre(): string
    {
        $this->loadMissing(['asignatura', 'asignaturaPropia']);

        return $this->asignatura?->nombre
            ?? $this->asignaturaPropia?->nombre
            ?? 'Asignatura';
    }

    public function areaNombre(): ?string
    {
        $this->loadMissing(['asignatura.area', 'asignaturaPropia.area']);

        return $this->asignatura?->area?->nombre
            ?? $this->asignaturaPropia?->area?->nombre;
    }
}
