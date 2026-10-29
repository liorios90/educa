<?php

namespace App\Models;

use Database\Factories\EstablecimientoTareaEntregaArchivoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'establecimiento_tarea_entrega_id',
    'path',
    'nombre_original',
    'mime',
    'size',
])]
class EstablecimientoTareaEntregaArchivo extends Model
{
    /** @use HasFactory<EstablecimientoTareaEntregaArchivoFactory> */
    use HasFactory;

    protected $table = 'establecimiento_tarea_entrega_archivos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    protected static function newFactory(): EstablecimientoTareaEntregaArchivoFactory
    {
        return EstablecimientoTareaEntregaArchivoFactory::new();
    }

    /**
     * @return BelongsTo<EstablecimientoTareaEntrega, $this>
     */
    public function entrega(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoTareaEntrega::class, 'establecimiento_tarea_entrega_id');
    }

    public function esPdf(): bool
    {
        $extension = strtolower(pathinfo((string) $this->nombre_original, PATHINFO_EXTENSION));

        return $extension === 'pdf' || $this->mime === 'application/pdf';
    }

    public function esImagen(): bool
    {
        $extension = strtolower(pathinfo((string) $this->nombre_original, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)
            || str_starts_with((string) $this->mime, 'image/');
    }
}
