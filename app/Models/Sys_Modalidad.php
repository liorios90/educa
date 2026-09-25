<?php

namespace App\Models;

use Database\Factories\SysModalidadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sys_Modalidad extends Model
{
    /** @use HasFactory<SysModalidadFactory> */
    use HasFactory;

    protected $table = 'sys_modalidades';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    protected static function newFactory(): SysModalidadFactory
    {
        return SysModalidadFactory::new();
    }

    /**
     * @return BelongsToMany<Establecimiento, $this>
     */
    public function establecimientos(): BelongsToMany
    {
        return $this->belongsToMany(Establecimiento::class, 'establecimiento_modalidades', 'modalidad_id', 'establecimiento_id')
            ->withTimestamps();
    }
}
