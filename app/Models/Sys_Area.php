<?php

namespace App\Models;

use Database\Factories\SysAreaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sys_Area extends Model
{
    /** @use HasFactory<SysAreaFactory> */
    use HasFactory;

    protected $table = 'sys_areas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'orden',
        'aparece_en_libreta',
        'subnivel_id',
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
            'aparece_en_libreta' => 'boolean',
        ];
    }

    protected static function newFactory(): SysAreaFactory
    {
        return SysAreaFactory::new();
    }

    /**
     * @return BelongsTo<Sys_Subnivel, $this>
     */
    public function subnivel(): BelongsTo
    {
        return $this->belongsTo(Sys_Subnivel::class, 'subnivel_id');
    }

    /**
     * @return HasMany<Sys_Asignatura, $this>
     */
    public function asignaturas(): HasMany
    {
        return $this->hasMany(Sys_Asignatura::class, 'area_id')->orderBy('orden')->orderBy('nombre')->orderBy('id');
    }

    /**
     * @param  string  $childType
     */
    protected function childRouteBindingRelationshipName($childType): string
    {
        return $childType === 'asignatura'
            ? 'asignaturas'
            : parent::childRouteBindingRelationshipName($childType);
    }
}
