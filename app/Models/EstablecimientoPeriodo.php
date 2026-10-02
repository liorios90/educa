<?php

namespace App\Models;

use App\Enums\EsquemaCiclo;
use Database\Factories\EstablecimientoPeriodoFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstablecimientoPeriodo extends Model
{
    /** @use HasFactory<EstablecimientoPeriodoFactory> */
    use HasFactory;

    protected $table = 'establecimiento_periodos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'establecimiento_modalidad_jornada_id',
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'esquema_ciclo',
        'numero_parciales',
        'porcentaje_examen_final',
        'porcentaje_proyecto_final',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'activo' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'activo' => 'boolean',
            'esquema_ciclo' => EsquemaCiclo::class,
            'numero_parciales' => 'integer',
            'porcentaje_examen_final' => 'decimal:2',
            'porcentaje_proyecto_final' => 'decimal:2',
        ];
    }

    protected static function newFactory(): EstablecimientoPeriodoFactory
    {
        return EstablecimientoPeriodoFactory::new();
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
     * @return HasMany<EstablecimientoAula, $this>
     */
    public function aulas(): HasMany
    {
        return $this->hasMany(EstablecimientoAula::class, 'establecimiento_periodo_id');
    }

    /**
     * @return HasMany<EstablecimientoPeriodoCiclo, $this>
     */
    public function ciclos(): HasMany
    {
        return $this->hasMany(EstablecimientoPeriodoCiclo::class, 'establecimiento_periodo_id')
            ->orderBy('orden');
    }

    #[Scope]
    protected function activo(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function activarEnOferta(): void
    {
        if (! $this->activo) {
            return;
        }

        static::query()
            ->where('establecimiento_modalidad_jornada_id', $this->establecimiento_modalidad_jornada_id)
            ->whereKeyNot($this->id)
            ->where('activo', true)
            ->lockForUpdate()
            ->update(['activo' => false]);
    }
}
