<?php

namespace App\Services;

use App\Enums\AsistenteProveedor;
use App\Exceptions\GeminiCredentialsMissingException;
use App\Models\Establecimiento;

class AsistenteService
{
    public function __construct(
        private GeminiService $gemini,
        private AsistenteCompatibleService $compatible,
    ) {}

    public function generate(string $prompt, Establecimiento $establecimiento, ?string $instruccionSistema = null): GeminiRespuesta
    {
        if (! $establecimiento->tieneCredencialesAsistente()) {
            throw new GeminiCredentialsMissingException;
        }

        return match ($establecimiento->proveedorAsistente()) {
            AsistenteProveedor::Gemini => $this->gemini->generate($prompt, $establecimiento, $instruccionSistema),
            AsistenteProveedor::Compatible => $this->compatible->generate($prompt, $establecimiento, $instruccionSistema),
        };
    }
}
