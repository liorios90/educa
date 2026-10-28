<?php

namespace App\Models;

use Database\Factories\NotificacionDestinatarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacionDestinatario extends Model
{
    /** @use HasFactory<NotificacionDestinatarioFactory> */
    use HasFactory;

    protected $table = 'notificacion_destinatarios';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'notificacion_id',
        'user_id',
        'leida_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'leida_at' => 'datetime',
        ];
    }

    protected static function newFactory(): NotificacionDestinatarioFactory
    {
        return NotificacionDestinatarioFactory::new();
    }

    /**
     * @return BelongsTo<Notificacion, $this>
     */
    public function notificacion(): BelongsTo
    {
        return $this->belongsTo(Notificacion::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
