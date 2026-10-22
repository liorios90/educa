<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equivalencia extends Model
{
    protected $table = 'equivalencias';

    public $timestamps = false;

    protected $fillable = ['establecimiento_periodo_id', 'nombre', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    /**
     * @return BelongsTo<EstablecimientoPeriodo, $this>
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoPeriodo::class, 'establecimiento_periodo_id');
    }

    /** @return HasMany<EquivalenciaDetalle, $this> */
    public function detalles(): HasMany
    {
        return $this->hasMany(EquivalenciaDetalle::class);
    }
}
