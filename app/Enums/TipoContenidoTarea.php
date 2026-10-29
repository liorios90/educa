<?php

namespace App\Enums;

enum TipoContenidoTarea: string
{
    case Texto = 'texto';
    case Url = 'url';
    case Pdf = 'pdf';
    case Imagen = 'imagen';

    public function label(): string
    {
        return match ($this) {
            self::Texto => 'Texto',
            self::Url => 'Enlace',
            self::Pdf => 'PDF',
            self::Imagen => 'Imagen',
        };
    }

    public function requiereArchivo(): bool
    {
        return $this === self::Pdf || $this === self::Imagen;
    }
}
