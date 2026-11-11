<?php

namespace App\Models;

use Database\Factories\SysDestrezaFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Sys_Destreza extends Model
{
    /** @use HasFactory<SysDestrezaFactory> */
    use HasFactory;

    protected $table = 'sys_destrezas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'asignatura_id',
        'grado_id',
        'criterio_evaluacion_id',
        'codigo',
        'descripcion',
        'imprescindible',
        'orden',
        'activo',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'orden' => 0,
        'activo' => true,
        'imprescindible' => true,
    ];

    protected static function newFactory(): SysDestrezaFactory
    {
        return SysDestrezaFactory::new();
    }

    protected static function booted(): void
    {
        static::deleting(function (Sys_Destreza $destreza): void {
            $tieneCalificaciones = $destreza->indicadores()
                ->whereHas('calificaciones')
                ->exists();

            if ($tieneCalificaciones) {
                throw ValidationException::withMessages([
                    'destreza' => 'Esta destreza ya tiene calificaciones registradas y no se puede eliminar.',
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
            'imprescindible' => 'boolean',
        ];
    }

    #[Scope]
    protected function activo(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * @return BelongsTo<Sys_Asignatura, $this>
     */
    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Sys_Asignatura::class, 'asignatura_id');
    }

    /**
     * @return BelongsTo<Sys_Grado, $this>
     */
    public function grado(): BelongsTo
    {
        return $this->belongsTo(Sys_Grado::class, 'grado_id');
    }

    /**
     * @return BelongsTo<Sys_CriterioEvaluacion, $this>
     */
    public function criterio(): BelongsTo
    {
        return $this->belongsTo(Sys_CriterioEvaluacion::class, 'criterio_evaluacion_id');
    }

    /**
     * @return HasMany<Sys_IndicadorEvaluacion, $this>
     */
    public function indicadores(): HasMany
    {
        return $this->hasMany(Sys_IndicadorEvaluacion::class, 'destreza_id')
            ->orderBy('orden')
            ->orderBy('id');
    }

    /**
     * @param  string  $childType
     */
    protected function childRouteBindingRelationshipName($childType): string
    {
        return $childType === 'indicador'
            ? 'indicadores'
            : parent::childRouteBindingRelationshipName($childType);
    }
}
