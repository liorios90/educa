<?php

namespace App\Models;

use Database\Factories\EstablecimientoGradoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoGrado extends Model
{
    /** @use HasFactory<EstablecimientoGradoFactory> */
    use HasFactory;

    protected $table = 'establecimiento_grados';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'subnivel_id',
        'grado_id',
        'nombre',
    ];

    protected static function newFactory(): EstablecimientoGradoFactory
    {
        return EstablecimientoGradoFactory::new();
    }

    /**
     * @return BelongsTo<Establecimiento, $this>
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    /**
     * @return BelongsTo<Sys_Subnivel, $this>
     */
    public function subnivel(): BelongsTo
    {
        return $this->belongsTo(Sys_Subnivel::class, 'subnivel_id');
    }

    /**
     * @return BelongsTo<Sys_Grado, $this>
     */
    public function grado(): BelongsTo
    {
        return $this->belongsTo(Sys_Grado::class, 'grado_id');
    }
}
