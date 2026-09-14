<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sys_Jornada extends Model
{
    // 1. Especificar el nombre correcto de la tabla de tu migración
    protected $table = 'sys_jornadas';

    // 2. Definir los campos permitidos para guardarse masivamente (create o update)
    protected $fillable = [
        'nombre',
        'descripcion',
    ];
}
