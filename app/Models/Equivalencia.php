<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equivalencia extends Model
{
    protected $table = 'equivalencias';

    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    /** @return HasMany<EquivalenciaDetalle, $this> */
    public function detalles(): HasMany
    {
        return $this->hasMany(EquivalenciaDetalle::class);
    }
}
