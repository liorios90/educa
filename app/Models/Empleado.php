<?php

namespace App\Models;

use Database\Factories\EmpleadoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Empleado extends Model
{
    /** @use HasFactory<EmpleadoFactory> */
    use HasFactory;

    protected $table = 'empleados';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'persona_id',
        'tipo_contrato_id',
        'cargo_id',
        'funcion_id',
        'horas',
        'anios_experiencia',
        'anios_instituto',
        'contacto_emergencia',
        'contacto_num',
        'usuario',
        'activo',
    ];

    protected static function newFactory(): EmpleadoFactory
    {
        return EmpleadoFactory::new();
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function tipoContrato(): BelongsTo
    {
        return $this->belongsTo(Sys_TipoContrato::class, 'tipo_contrato_id');
    }

    public function funcion(): BelongsTo
    {
        return $this->belongsTo(Sys_Funcion::class, 'funcion_id');
    }
}
