<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquivalenciaDetalle extends Model
{
    protected $table = 'equivalencias_detalles';

    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion', 'valor', 'minimo', 'maximo', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'minimo' => 'decimal:2', 'maximo' => 'decimal:2'];
    }

    /** @return BelongsTo<Equivalencia, $this> */
    public function equivalencia(): BelongsTo
    {
        return $this->belongsTo(Equivalencia::class);
    }
}
