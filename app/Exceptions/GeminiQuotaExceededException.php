<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class GeminiQuotaExceededException extends Exception implements ShouldntReport
{
    public function __construct(string $recurso = 'consultas')
    {
        parent::__construct(
            "Este instituto ya no tiene {$recurso} disponibles este mes. Pide al administrador que aumente el cupo en Configuración → Asistente."
        );
    }
}
