<?php

namespace App\Models;

use Database\Factories\NotificacionArchivoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacionArchivo extends Model
{
    /** @use HasFactory<NotificacionArchivoFactory> */
    use HasFactory;

    protected $table = 'notificacion_archivos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'notificacion_id',
        'path',
        'nombre_original',
        'mime',
        'size',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    protected static function newFactory(): NotificacionArchivoFactory
    {
        return NotificacionArchivoFactory::new();
    }

    /**
     * @return BelongsTo<Notificacion, $this>
     */
    public function notificacion(): BelongsTo
    {
        return $this->belongsTo(Notificacion::class);
    }

    public function esPdf(): bool
    {
        $extension = strtolower(pathinfo((string) $this->nombre_original, PATHINFO_EXTENSION));

        return $extension === 'pdf' || $this->mime === 'application/pdf';
    }

    public function esImagen(): bool
    {
        $extension = strtolower(pathinfo((string) $this->nombre_original, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)
            || str_starts_with((string) $this->mime, 'image/');
    }

    public function seMuestraEnLinea(): bool
    {
        return $this->esPdf() || $this->esImagen();
    }

    public function tipoContenido(): string
    {
        if ($this->esPdf()) {
            return 'application/pdf';
        }

        if ($this->esImagen()) {
            return match (strtolower(pathinfo((string) $this->nombre_original, PATHINFO_EXTENSION))) {
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => str_starts_with((string) $this->mime, 'image/') ? (string) $this->mime : 'image/jpeg',
            };
        }

        return $this->mime !== '' ? (string) $this->mime : 'application/octet-stream';
    }
}
