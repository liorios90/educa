<?php

namespace App\Services;

use App\Enums\ModoDecimal;
use App\Models\Establecimiento;

class DecimalesEstablecimiento
{
    public function decimales(?Establecimiento $establecimiento): int
    {
        $decimales = $establecimiento?->decimales_calificacion;

        if (! is_numeric($decimales)) {
            return 2;
        }

        return max(0, min(4, (int) $decimales));
    }

    public function modo(?Establecimiento $establecimiento): ModoDecimal
    {
        return $establecimiento?->modo_decimal ?? ModoDecimal::Aproximar;
    }

    public function aplicar(float $valor, ?Establecimiento $establecimiento): float
    {
        $decimales = $this->decimales($establecimiento);

        if ($this->modo($establecimiento) === ModoDecimal::Truncar) {
            return $this->truncar($valor, $decimales);
        }

        return round($valor, $decimales, PHP_ROUND_HALF_UP);
    }

    public function formatear(float $valor, ?Establecimiento $establecimiento): string
    {
        return number_format($this->aplicar($valor, $establecimiento), $this->decimales($establecimiento), '.', '');
    }

    private function truncar(float $valor, int $decimales): float
    {
        $factor = 10 ** $decimales;
        $ajustado = $valor >= 0
            ? floor($valor * $factor + 1e-8)
            : ceil($valor * $factor - 1e-8);

        return round($ajustado / $factor, $decimales, PHP_ROUND_HALF_UP);
    }
}
