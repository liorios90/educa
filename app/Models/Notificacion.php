<?php

namespace App\Models;

use Database\Factories\NotificacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notificacion extends Model
{
    /** @use HasFactory<NotificacionFactory> */
    use HasFactory;

    protected $table = 'notificaciones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'remitente_id',
        'asunto',
        'cuerpo',
    ];

    protected static function newFactory(): NotificacionFactory
    {
        return NotificacionFactory::new();
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
    public function remitente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'remitente_id');
    }

    /**
     * @return HasMany<NotificacionDestinatario, $this>
     */
    public function destinatarios(): HasMany
    {
        return $this->hasMany(NotificacionDestinatario::class);
    }
}
