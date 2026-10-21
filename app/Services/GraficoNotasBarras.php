<?php

namespace App\Services;

use Illuminate\Support\Collection;

class GraficoNotasBarras
{
    /**
     * @param  Collection<int, array{estudiante: string, nota: float|null, etiqueta: string, porcentaje: float, bajoMinimo: bool}>  $puntos
     * @return array{
     *     ancho: int,
     *     alto: int,
     *     baseY: float,
     *     lineaMinimaY: float,
     *     ticks: list<array{etiqueta: string, y: float}>,
     *     barras: Collection<int, array{estudiante: string, nota: float|null, etiqueta: string, porcentaje: float, bajoMinimo: bool, x: float, y: float, ancho: float, alto: float, centroX: float, color: string}>
     * }
     */
    public function dibujar(Collection $puntos, float $notaMaxima, float $notaMinima): array
    {
        $cantidad = max(1, $puntos->count());
        $margenIzquierdo = 48.0;
        $margenDerecho = 24.0;
        $margenSuperior = 28.0;
        $margenInferior = 120.0;
        $alto = 440;
        $plotAlto = $alto - $margenSuperior - $margenInferior;
        $plotAncho = max(840.0, $cantidad * 56.0);
        $huecoTotal = $plotAncho / $cantidad;
        $anchoBarra = min(48.0, max(24.0, $huecoTotal * 0.55));
        $hueco = $huecoTotal - $anchoBarra;
        $ancho = (int) round($margenIzquierdo + $plotAncho + $margenDerecho);
        $maxima = $notaMaxima > 0 ? $notaMaxima : 10.0;
        $baseY = $margenSuperior + $plotAlto;
        $lineaMinimaY = $baseY - (($notaMinima / $maxima) * $plotAlto);

        $barras = $puntos
            ->values()
            ->map(function (array $punto, int $indice) use ($margenIzquierdo, $anchoBarra, $hueco, $plotAlto, $baseY): array {
                $altura = ($punto['porcentaje'] / 100) * $plotAlto;
                $x = $margenIzquierdo + ($indice * ($anchoBarra + $hueco)) + ($hueco / 2);

                return [
                    ...$punto,
                    'x' => round($x, 2),
                    'y' => round($baseY - $altura, 2),
                    'ancho' => $anchoBarra,
                    'alto' => round($altura, 2),
                    'centroX' => round($x + ($anchoBarra / 2), 2),
                    'color' => $punto['bajoMinimo'] ? '#dc2626' : '#4f46e5',
                ];
            });

        return [
            'ancho' => $ancho,
            'alto' => $alto,
            'baseY' => $baseY,
            'lineaMinimaY' => round($lineaMinimaY, 2),
            'ticks' => [
                ['etiqueta' => number_format($maxima, 0, '.', ''), 'y' => $margenSuperior],
                ['etiqueta' => number_format($notaMinima, 0, '.', ''), 'y' => $lineaMinimaY],
                ['etiqueta' => '0', 'y' => $baseY],
            ],
            'barras' => $barras,
        ];
    }
}
