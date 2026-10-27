<?php

namespace App\Models;

use App\Enums\UsoEquivalencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equivalencia extends Model
{
    protected $table = 'equivalencias';

    public $timestamps = false;

    protected $attributes = [
        'uso' => 'asignatura',
    ];

    protected $fillable = ['establecimiento_periodo_id', 'nombre', 'descripcion', 'uso', 'activo'];

    protected function casts(): array
    {
        return [
            'uso' => UsoEquivalencia::class,
            'activo' => 'boolean',
        ];
    }

    public function esComportamiento(): bool
    {
        return $this->uso === UsoEquivalencia::Comportamiento;
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
