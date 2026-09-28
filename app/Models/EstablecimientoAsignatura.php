<?php

namespace App\Models;

use App\Enums\TipoCalificacion;
use Database\Factories\EstablecimientoAsignaturaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'aparece_en_libreta',
        'horas_semanales',
        'tipo_calificacion',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'aparece_en_libreta' => true,
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
}
