<?php

namespace App\Models;

use Database\Factories\SysModalidadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sys_Modalidad extends Model
{
    /** @use HasFactory<SysModalidadFactory> */
    use HasFactory;

    protected $table = 'sys_modalidades';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    protected static function newFactory(): SysModalidadFactory
    {
        return SysModalidadFactory::new();
    }
}
