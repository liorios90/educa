<?php

namespace App\Models;

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
