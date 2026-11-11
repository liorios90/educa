<?php

namespace App\Models;

use Database\Factories\SysCriterioEvaluacionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sys_CriterioEvaluacion extends Model
{
    /** @use HasFactory<SysCriterioEvaluacionFactory> */
    use HasFactory;

    protected $table = 'sys_criterios_evaluacion';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'asignatura_id',
        'grado_id',
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

    protected static function newFactory(): SysCriterioEvaluacionFactory
    {
        return SysCriterioEvaluacionFactory::new();
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
     * @return HasMany<Sys_Destreza, $this>
     */
    public function destrezas(): HasMany
    {
        return $this->hasMany(Sys_Destreza::class, 'criterio_evaluacion_id')
            ->orderBy('orden')
            ->orderBy('id');
    }
}
