<?php

namespace App\Enums;

enum Regimen: string
{
    case Costa = 'Costa';
    case Sierra = 'Sierra';

    public function label(): string
    {
        return $this->value;
    }
}
