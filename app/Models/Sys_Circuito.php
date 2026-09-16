<?php

namespace App\Models;

use Database\Factories\SysCircuitoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sys_Circuito extends Model
{
    /** @use HasFactory<SysCircuitoFactory> */
    use HasFactory;

    protected $table = 'sys_circuitos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'usuario',
        'activo',
        'distrito_id',
    ];

    protected static function newFactory(): SysCircuitoFactory
    {
        return SysCircuitoFactory::new();
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(Sys_Distrito::class, 'distrito_id');
    }

    public function establecimientos(): HasMany
    {
        return $this->hasMany(Establecimiento::class, 'circuito_id');
    }
}
