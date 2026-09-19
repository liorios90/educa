<?php

namespace App\Models;

use Database\Factories\SysTipoContratoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * @return HasMany<Empleado, $this>
     */
    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'tipo_contrato_id');
    }
}
