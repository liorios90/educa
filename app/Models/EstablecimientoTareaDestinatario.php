<?php

namespace App\Models;

use Database\Factories\EstablecimientoTareaDestinatarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'establecimiento_tarea_id',
    'alumno_id',
    'nota',
    'observacion',
    'calificada_at',
])]
class EstablecimientoTareaDestinatario extends Model
{
    /** @use HasFactory<EstablecimientoTareaDestinatarioFactory> */
    use HasFactory;

    protected $table = 'establecimiento_tarea_destinatarios';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nota' => 'decimal:2',
            'calificada_at' => 'datetime',
        ];
    }

    protected static function newFactory(): EstablecimientoTareaDestinatarioFactory
    {
        return EstablecimientoTareaDestinatarioFactory::new();
    }

    /**
     * @return BelongsTo<EstablecimientoTarea, $this>
     */
    public function tarea(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoTarea::class, 'establecimiento_tarea_id');
    }

    /**
     * @return BelongsTo<Alumno, $this>
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }
}
