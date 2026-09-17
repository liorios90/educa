<?php

namespace App\Models;

use Database\Factories\SysPaisFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sys_Pais extends Model
{
    /** @use HasFactory<SysPaisFactory> */
    use HasFactory;

    protected $table = 'sys_paises';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'usuario',
        'activo',
    ];

    protected static function newFactory(): SysPaisFactory
    {
        return SysPaisFactory::new();
    }

    public function provincias(): HasMany
    {
        return $this->hasMany(Sys_Provincia::class, 'pais_id');
    }
}
