<?php

namespace App\Models;

use Database\Factories\SysFuncionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sys_Funcion extends Model
{
    /** @use HasFactory<SysFuncionFactory> */
    use HasFactory;

    protected $table = 'sys_funciones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'usuario',
        'activo',
    ];

    protected static function newFactory(): SysFuncionFactory
    {
        return SysFuncionFactory::new();
    }
}
