<?php

namespace App\Models;

use Database\Factories\ImportDataDetalleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportDataDetalle extends Model
{
    /** @use HasFactory<ImportDataDetalleFactory> */
    use HasFactory;

    protected $table = 'import_data_detalles';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'import_data_id',
        'num_fila',
        'identificacion',
        'descripcion',
    ];

    protected static function newFactory(): ImportDataDetalleFactory
    {
        return ImportDataDetalleFactory::new();
    }

    public function importData(): BelongsTo
    {
        return $this->belongsTo(ImportData::class);
    }

    public function wasImported(): bool
    {
        return $this->descripcion === 'Importado correctamente';
    }
}
