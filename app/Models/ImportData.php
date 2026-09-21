<?php

namespace App\Models;

use Database\Factories\ImportDataFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportData extends Model
{
    /** @use HasFactory<ImportDataFactory> */
    use HasFactory;

    protected $table = 'import_datas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'tablas',
        'tipo_archivo',
        'mensaje',
        'establecimiento_id',
        'usuario',
        'activo',
    ];

    protected static function newFactory(): ImportDataFactory
    {
        return ImportDataFactory::new();
    }

    /**
     * @return HasMany<ImportDataDetalle, $this>
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(ImportDataDetalle::class)->orderBy('num_fila')->orderBy('id');
    }

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }
}
