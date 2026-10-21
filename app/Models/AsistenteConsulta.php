<?php

namespace App\Models;

use Database\Factories\AsistenteConsultaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsistenteConsulta extends Model
{
    /** @use HasFactory<AsistenteConsultaFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'user_id',
        'usuario',
        'pregunta',
    ];

    protected static function newFactory(): AsistenteConsultaFactory
    {
        return AsistenteConsultaFactory::new();
    }

    /**
     * @return BelongsTo<Establecimiento, $this>
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
