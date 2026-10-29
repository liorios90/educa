<?php

namespace App\Support;

final class HtmlSeguro
{
    public static function limpiar(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $permitidas = '<p><br><strong><b><em><i><u><ul><ol><li><a><h3><h4><blockquote>';
        $limpio = strip_tags($html, $permitidas);
        $limpio = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $limpio) ?? $limpio;
        $limpio = preg_replace('/href\s*=\s*([\'"])\s*(javascript:|data:)/i', 'href=$1#', $limpio) ?? $limpio;

        return trim($limpio);
    }
}
