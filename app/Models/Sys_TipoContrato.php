<?php

namespace App\Models;

use Database\Factories\SysTipoContratoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sys_TipoContrato extends Model
{
    /** @use HasFactory<SysTipoContratoFactory> */
    use HasFactory;

    protected $table = 'sys_tipo_contratos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'usuario',
        'activo',
    ];

    protected static function newFactory(): SysTipoContratoFactory
    {
        return SysTipoContratoFactory::new();
    }
}
