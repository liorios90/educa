<?php

namespace App\Models;

use Database\Factories\SysZonaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sys_Zona extends Model
{
    /** @use HasFactory<SysZonaFactory> */
    use HasFactory;

    protected $table = 'sys_zonas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'usuario',
        'activo',
    ];

    protected static function newFactory(): SysZonaFactory
    {
        return SysZonaFactory::new();
    }

    public function distritos(): HasMany
    {
        return $this->hasMany(Sys_Distrito::class, 'zona_id');
    }
}
