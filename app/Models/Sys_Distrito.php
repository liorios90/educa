<?php

namespace App\Models;

use Database\Factories\SysDistritoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sys_Distrito extends Model
{
    /** @use HasFactory<SysDistritoFactory> */
    use HasFactory;

    protected $table = 'sys_distritos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'provincia',
        'descripcion',
        'usuario',
        'activo',
        'zona_id',
    ];

    protected static function newFactory(): SysDistritoFactory
    {
        return SysDistritoFactory::new();
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Sys_Zona::class, 'zona_id');
    }

    public function circuitos(): HasMany
    {
        return $this->hasMany(Sys_Circuito::class, 'distrito_id');
    }
}
