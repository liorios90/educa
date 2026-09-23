<?php

namespace App\Models;

use Database\Factories\SysGradoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sys_Grado extends Model
{
    /** @use HasFactory<SysGradoFactory> */
    use HasFactory;

    protected $table = 'sys_grados';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'siglas',
        'descripcion',
        'subnivel_id',
    ];

    protected static function newFactory(): SysGradoFactory
    {
        return SysGradoFactory::new();
    }

    /**
     * @return BelongsTo<Sys_Subnivel, $this>
     */
    public function subnivel(): BelongsTo
    {
        return $this->belongsTo(Sys_Subnivel::class, 'subnivel_id');
    }

    /**
     * @return BelongsToMany<Establecimiento, $this>
     */
    public function establecimientos(): BelongsToMany
    {
        return $this->belongsToMany(Establecimiento::class, 'establecimiento_grados', 'grado_id', 'establecimiento_id')
            ->withPivot('subnivel_id')
            ->withTimestamps();
    }
}
