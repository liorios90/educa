<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sys_Nivel extends Model
{
  protected $table = 'sys_niveles';

  // 2. Habilitar la asignación masiva para estos campos
  protected $fillable = [
      'nombre',
      'descripcion',
  ];
}
