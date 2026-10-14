<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoInsumo extends Model
{
    protected $table = 'tipos_insumos';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo_calificacion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<TipoInsumoDetalle, $this>
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(TipoInsumoDetalle::class);
    }
}
