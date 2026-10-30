<?php

namespace App\Enums;

enum AsistenteProveedor: string
{
    case Gemini = 'gemini';
    case Compatible = 'compatible';

    public function label(): string
    {
        return match ($this) {
            self::Gemini => 'Gemini',
            self::Compatible => 'API compatible con OpenAI',
        };
    }
}
