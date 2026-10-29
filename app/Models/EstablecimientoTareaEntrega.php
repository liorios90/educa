<?php

namespace App\Models;

use Database\Factories\EstablecimientoTareaEntregaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'establecimiento_tarea_id',
    'alumno_id',
    'mensaje',
])]
class EstablecimientoTareaEntrega extends Model
{
    /** @use HasFactory<EstablecimientoTareaEntregaFactory> */
    use HasFactory;

    protected $table = 'establecimiento_tarea_entregas';

    protected static function newFactory(): EstablecimientoTareaEntregaFactory
    {
        return EstablecimientoTareaEntregaFactory::new();
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

    /**
     * @return HasMany<EstablecimientoTareaEntregaArchivo, $this>
     */
    public function archivos(): HasMany
    {
        return $this->hasMany(EstablecimientoTareaEntregaArchivo::class, 'establecimiento_tarea_entrega_id');
    }
}
