<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class GeminiCredentialsMissingException extends Exception implements ShouldntReport
{
    public function __construct()
    {
        parent::__construct('Este instituto no tiene configurada la clave de Gemini. Pide al administrador que la ingrese en Configuración.');
    }
}
