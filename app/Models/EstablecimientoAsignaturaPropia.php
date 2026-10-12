<?php

namespace App\Models;

use App\Enums\TipoAsignatura;
use Database\Factories\EstablecimientoAsignaturaPropiaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstablecimientoAsignaturaPropia extends Model
{
    /** @use HasFactory<EstablecimientoAsignaturaPropiaFactory> */
    use HasFactory;

    protected $table = 'establecimiento_asignaturas_propias';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'establecimiento_modalidad_jornada_id',
        'area_id',
        'nombre',
        'tipo_asignatura',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_asignatura' => TipoAsignatura::class,
        ];
    }

    protected static function newFactory(): EstablecimientoAsignaturaPropiaFactory
    {
        return EstablecimientoAsignaturaPropiaFactory::new();
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
     * @return BelongsTo<Sys_Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Sys_Area::class, 'area_id');
    }

    /**
     * @return HasMany<EstablecimientoAsignatura, $this>
     */
    public function malla(): HasMany
    {
        return $this->hasMany(EstablecimientoAsignatura::class, 'asignatura_propia_id');
    }
}
