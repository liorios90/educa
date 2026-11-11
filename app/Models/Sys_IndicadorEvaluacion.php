<?php

namespace App\Models;

use Database\Factories\SysIndicadorEvaluacionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Sys_IndicadorEvaluacion extends Model
{
    /** @use HasFactory<SysIndicadorEvaluacionFactory> */
    use HasFactory;

    protected $table = 'sys_indicadores_evaluacion';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'destreza_id',
        'codigo',
        'descripcion',
        'orden',
        'activo',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'orden' => 0,
        'activo' => true,
    ];

    protected static function newFactory(): SysIndicadorEvaluacionFactory
    {
        return SysIndicadorEvaluacionFactory::new();
    }

    protected static function booted(): void
    {
        static::deleting(function (Sys_IndicadorEvaluacion $indicador): void {
            if ($indicador->calificaciones()->exists()) {
                throw ValidationException::withMessages([
                    'indicador' => 'Este indicador ya tiene calificaciones registradas y no se puede eliminar.',
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
            'orden' => 'integer',
            'activo' => 'boolean',
        ];
    }

    #[Scope]
    protected function activo(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * @return BelongsTo<Sys_Destreza, $this>
     */
    public function destreza(): BelongsTo
    {
        return $this->belongsTo(Sys_Destreza::class, 'destreza_id');
    }

    /**
     * @return HasMany<EstablecimientoIndicadorCalificacion, $this>
     */
    public function calificaciones(): HasMany
    {
        return $this->hasMany(EstablecimientoIndicadorCalificacion::class, 'indicador_evaluacion_id');
    }
}
