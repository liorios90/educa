<?php

namespace App\Models;

use Database\Factories\SysProvinciaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sys_Provincia extends Model
{
    /** @use HasFactory<SysProvinciaFactory> */
    use HasFactory;

    protected $table = 'sys_provincias';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'pais_id',
        'usuario',
        'activo',
    ];

    protected static function newFactory(): SysProvinciaFactory
    {
        return SysProvinciaFactory::new();
    }

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Sys_Pais::class, 'pais_id');
    }
}
