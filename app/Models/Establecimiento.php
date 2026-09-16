<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Establecimiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'direccion',
        'telefono',
        'representante',
        'codigo_amie',
        'regimen',
        'email',
        'usuario',
        'activo',
        'logo',
        'only_visible',
        'mision',
        'vision',
        'ideario',
        'grupo_amie',
        'zona_id',
        'distrito_id',
        'circuito_id',
    ];

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Sys_Zona::class, 'zona_id');
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(Sys_Distrito::class, 'distrito_id');
    }

    public function circuito(): BelongsTo
    {
        return $this->belongsTo(Sys_Circuito::class, 'circuito_id');
    }
}
