<?php

namespace App\Models;

use App\Enums\TipoCriterioComportamiento;
use Database\Factories\SysCriterioComportamientoFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\ValidationException;

class Sys_CriterioComportamiento extends Model
{
    /** @use HasFactory<SysCriterioComportamientoFactory> */
    use HasFactory;

    protected $table = 'sys_criterios_comportamiento';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'codigo',
        'descripcion',
        'tipo',
        'activo',
        'obligatorio',
        'usuario',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'activo' => true,
        'obligatorio' => false,
    ];

    protected static function newFactory(): SysCriterioComportamientoFactory
    {
        return SysCriterioComportamientoFactory::new();
    }

    protected static function booted(): void
    {
        static::deleting(function (Sys_CriterioComportamiento $criterio): void {
            if ($criterio->periodos()->exists()) {
                throw ValidationException::withMessages([
                    'criterio' => 'Este criterio ya está elegido en un periodo. Desactívalo en lugar de eliminarlo.',
                ]);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoCriterioComportamiento::class,
            'activo' => 'boolean',
            'obligatorio' => 'boolean',
        ];
    }

    #[Scope]
    protected function activo(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * @return BelongsToMany<EstablecimientoPeriodo, $this>
     */
    public function periodos(): BelongsToMany
    {
        return $this->belongsToMany(
            EstablecimientoPeriodo::class,
            'establecimiento_periodo_criterios_comportamiento',
            'criterio_comportamiento_id',
            'establecimiento_periodo_id',
        );
    }
}
