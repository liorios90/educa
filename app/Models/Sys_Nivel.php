<?php

namespace App\Models;

use Database\Factories\SysNivelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sys_Nivel extends Model
{
    /** @use HasFactory<SysNivelFactory> */
    use HasFactory;

    protected $table = 'sys_niveles';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'siglas',
        'descripcion',
    ];

    protected static function newFactory(): SysNivelFactory
    {
        return SysNivelFactory::new();
    }

    /**
     * @return HasMany<Sys_Subnivel, $this>
     */
    public function subniveles(): HasMany
    {
        return $this->hasMany(Sys_Subnivel::class, 'nivel_id')->orderBy('nombre')->orderBy('id');
    }

    /**
     * @param  string  $childType
     */
    protected function childRouteBindingRelationshipName($childType): string
    {
        return $childType === 'subnivel'
            ? 'subniveles'
            : parent::childRouteBindingRelationshipName($childType);
    }
}
