<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoInsumoDetalle extends Model
{
    protected $table = 'tipos_insumos_detalles';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'valor',
        'porcentaje',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'porcentaje' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<TipoInsumo, $this>
     */
    public function tipoInsumo(): BelongsTo
    {
        return $this->belongsTo(TipoInsumo::class);
    }
}
