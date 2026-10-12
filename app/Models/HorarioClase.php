<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioClase extends Model
{
    protected $fillable = [
        'establecimiento_aula_id',
        'establecimiento_asignatura_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
    ];

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
        ];
    }

    public function aula(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoAula::class, 'establecimiento_aula_id');
    }

    public function malla(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoAsignatura::class, 'establecimiento_asignatura_id');
    }
}
