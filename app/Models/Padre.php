<?php

namespace App\Models;

use Database\Factories\PadreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Padre extends Model
{
    /** @use HasFactory<PadreFactory> */
    use HasFactory;

    protected $table = 'padres';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'persona_id',
        'estado_civil_id',
        'vive_con_estudiante',
        'titulo',
        'usuario',
        'activo',
    ];

    protected static function newFactory(): PadreFactory
    {
        return PadreFactory::new();
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }
}
