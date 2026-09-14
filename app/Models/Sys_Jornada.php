<?php

namespace App\Models;

use Database\Factories\SysJornadaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sys_Jornada extends Model
{
    /** @use HasFactory<SysJornadaFactory> */
    use HasFactory;

    protected $table = 'sys_jornadas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    protected static function newFactory(): SysJornadaFactory
    {
        return SysJornadaFactory::new();
    }
}
