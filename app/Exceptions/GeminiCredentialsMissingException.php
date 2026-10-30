<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class GeminiCredentialsMissingException extends Exception implements ShouldntReport
{
    public function __construct(string $message = 'Este instituto no tiene configurado el asistente. Pide al administrador que lo configure en Configuración.')
    {
        parent::__construct($message);
    }
}
