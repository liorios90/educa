<?php

namespace App\Models;

use Database\Factories\SysAsignaturaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sys_Asignatura extends Model
{
    /** @use HasFactory<SysAsignaturaFactory> */
    use HasFactory;

    protected $table = 'sys_asignaturas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'orden',
        'horas_semanales',
        'aparece_en_libreta',
        'area_id',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'orden' => 0,
        'aparece_en_libreta' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'horas_semanales' => 'integer',
            'aparece_en_libreta' => 'boolean',
        ];
    }

    protected static function newFactory(): SysAsignaturaFactory
    {
        return SysAsignaturaFactory::new();
    }

    /**
     * @return BelongsTo<Sys_Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Sys_Area::class, 'area_id');
    }

    /**
     * @return BelongsToMany<Sys_Grado, $this>
     */
    public function gradosCurriculares(): BelongsToMany
    {
        return $this->belongsToMany(Sys_Grado::class, 'sys_asignatura_grado', 'asignatura_id', 'grado_id')
            ->withPivot('obligatoria')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Sys_Destreza, $this>
     */
    public function destrezas(): HasMany
    {
        return $this->hasMany(Sys_Destreza::class, 'asignatura_id')
            ->orderBy('orden')
            ->orderBy('id');
    }

    /**
     * @return HasMany<Sys_CriterioEvaluacion, $this>
     */
    public function criterios(): HasMany
    {
        return $this->hasMany(Sys_CriterioEvaluacion::class, 'asignatura_id')
            ->orderBy('orden')
            ->orderBy('id');
    }

    /**
     * @param  string  $childType
     */
    protected function childRouteBindingRelationshipName($childType): string
    {
        return match ($childType) {
            'destreza' => 'destrezas',
            'criterio' => 'criterios',
            default => parent::childRouteBindingRelationshipName($childType),
        };
    }
}
