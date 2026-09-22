<?php

namespace App\Enums;

enum NavigationGroupDisplay: string
{
    case Screen = 'screen';
    case Sidebar = 'sidebar';

    public function label(): string
    {
        return match ($this) {
            self::Screen => 'Como pantalla de botones',
            self::Sidebar => 'Como submenú en la barra izquierda',
        };
    }

    public function displayedRoute(): string
    {
        return match ($this) {
            self::Screen => 'Botones',
            self::Sidebar => 'Barra',
        };
    }
}
