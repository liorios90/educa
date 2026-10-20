<?php

namespace App\Services;

use App\Exceptions\GeminiQuotaExceededException;
use App\Models\Establecimiento;
use Illuminate\Support\Facades\DB;

class AsistenteCupo
{
    public function resumen(Establecimiento $establecimiento): AsistenteUso
    {
        return $this->usoDe($establecimiento);
    }

    public function exigirDisponible(Establecimiento $establecimiento): void
    {
        $uso = $this->usoDe($establecimiento);

        if ($uso->preguntasRestantes === 0) {
            throw new GeminiQuotaExceededException('preguntas');
        }

        if ($uso->tokensRestantes === 0) {
            throw new GeminiQuotaExceededException('tokens');
        }
    }

    public function registrar(Establecimiento $establecimiento, int $tokens): void
    {
        DB::transaction(function () use ($establecimiento, $tokens): void {
            $row = Establecimiento::query()
                ->whereKey($establecimiento->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->reiniciarSiCambioElMes($row);

            $row->update([
                'gemini_preguntas_usadas' => (int) $row->gemini_preguntas_usadas + 1,
                'gemini_tokens_usados' => (int) $row->gemini_tokens_usados + max(0, $tokens),
                'gemini_uso_mes' => $this->mesActual(),
            ]);
        });
    }

    private function usoDe(Establecimiento $establecimiento): AsistenteUso
    {
        $mismoMes = $establecimiento->gemini_uso_mes === $this->mesActual();
        $preguntasUsadas = $mismoMes ? (int) $establecimiento->gemini_preguntas_usadas : 0;
        $tokensUsados = $mismoMes ? (int) $establecimiento->gemini_tokens_usados : 0;
        $preguntasLimite = max(1, (int) $establecimiento->gemini_preguntas_limite);
        $tokensLimite = max(1, (int) $establecimiento->gemini_tokens_limite);

        return new AsistenteUso(
            preguntasUsadas: $preguntasUsadas,
            preguntasLimite: $preguntasLimite,
            preguntasRestantes: max(0, $preguntasLimite - $preguntasUsadas),
            tokensUsados: $tokensUsados,
            tokensLimite: $tokensLimite,
            tokensRestantes: max(0, $tokensLimite - $tokensUsados),
            mes: $this->mesActual(),
            mesEtiqueta: $this->mesEtiqueta(),
        );
    }

    private function reiniciarSiCambioElMes(Establecimiento $establecimiento): void
    {
        if ($establecimiento->gemini_uso_mes === $this->mesActual()) {
            return;
        }

        $establecimiento->fill([
            'gemini_preguntas_usadas' => 0,
            'gemini_tokens_usados' => 0,
            'gemini_uso_mes' => $this->mesActual(),
        ]);
    }

    private function mesActual(): string
    {
        return now()->format('Y-m');
    }

    private function mesEtiqueta(): string
    {
        $meses = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        return ($meses[(int) now()->format('n')] ?? now()->format('F')).' '.now()->format('Y');
    }
}
