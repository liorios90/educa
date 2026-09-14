<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sys_Modalidad extends Model
{
    // 1. Apuntar a la tabla correcta de la migración
    protected $table = 'sys_modalidades';

    // 2. Habilitar la asignación masiva para estos campos
    protected $fillable = [
        'nombre',
        'descripcion',
    ];
}
