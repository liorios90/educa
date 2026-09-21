<?php

namespace App\Models;

use App\Enums\TipoCalificacion;
use Database\Factories\SysSubnivelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Sys_Subnivel extends Model
{
    /** @use HasFactory<SysSubnivelFactory> */
    use HasFactory;

    protected $table = 'sys_subniveles';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'siglas',
        'descripcion',
        'tipo_calificacion',
        'nivel_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_calificacion' => TipoCalificacion::class,
        ];
    }

    protected static function newFactory(): SysSubnivelFactory
    {
        return SysSubnivelFactory::new();
    }

    /**
     * @return BelongsTo<Sys_Nivel, $this>
     */
    public function nivel(): BelongsTo
    {
        return $this->belongsTo(Sys_Nivel::class, 'nivel_id');
    }

    /**
     * @return HasMany<Sys_Grado, $this>
     */
    public function grados(): HasMany
    {
        return $this->hasMany(Sys_Grado::class, 'subnivel_id')->orderBy('nombre')->orderBy('id');
    }

    /**
     * @return HasMany<Sys_Area, $this>
     */
    public function areas(): HasMany
    {
        return $this->hasMany(Sys_Area::class, 'subnivel_id')->orderBy('orden')->orderBy('nombre')->orderBy('id');
    }

    /**
     * @return HasManyThrough<Sys_Asignatura, Sys_Area, $this>
     */
    public function asignaturas(): HasManyThrough
    {
        return $this->hasManyThrough(Sys_Asignatura::class, Sys_Area::class, 'subnivel_id', 'area_id');
    }

    /**
     * @param  string  $childType
     */
    protected function childRouteBindingRelationshipName($childType): string
    {
        return match ($childType) {
            'grado' => 'grados',
            'area' => 'areas',
            default => parent::childRouteBindingRelationshipName($childType),
        };
    }
}
