<?php

namespace App\Services;

readonly class HistorialAcademico
{
    /**
     * @param  list<array{fecha: string, actividad: string, insumo: string, asignatura: string, ciclo: string, parcial: int, nota: string}>  $notasInsumo
     * @param  list<array{asignatura: string, ciclo: int, parcial: int, nota: string}>  $calificacionesPeriodo
     * @param  list<array{fecha: string, estado: string, asignatura: string|null}>  $asistencias
     */
    public function __construct(
        public string $estudiante,
        public string $aula,
        public string $asignatura,
        public string $periodo,
        public string $ciclo,
        public int $parcial,
        public string $escala,
        public float $notaMinimaAprobacion,
        public array $notasInsumo,
        public array $calificacionesPeriodo,
        public array $asistencias,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toPromptPayload(): array
    {
        return [
            'estudiante' => $this->estudiante,
            'contexto' => [
                'aula' => $this->aula,
                'asignatura' => $this->asignatura,
                'periodo' => $this->periodo,
                'ciclo' => $this->ciclo,
                'parcial' => $this->parcial,
                'escala' => $this->escala,
                'nota_minima_aprobacion' => $this->notaMinimaAprobacion,
            ],
            'notas_insumo' => $this->notasInsumo,
            'calificaciones_periodo' => $this->calificacionesPeriodo,
            'asistencias' => $this->asistencias,
            'resumen_asistencia' => $this->resumenAsistencia(),
        ];
    }

    /**
     * @return array{total: int, presentes: int, faltas: int, atrasos: int, justificadas: int}
     */
    public function resumenAsistencia(): array
    {
        $conteo = [
            'total' => count($this->asistencias),
            'presentes' => 0,
            'faltas' => 0,
            'atrasos' => 0,
            'justificadas' => 0,
        ];

        foreach ($this->asistencias as $asistencia) {
            match ($asistencia['estado']) {
                'Presente' => $conteo['presentes']++,
                'Falta' => $conteo['faltas']++,
                'Atraso' => $conteo['atrasos']++,
                'Falta justificada' => $conteo['justificadas']++,
                default => null,
            };
        }

        return $conteo;
    }

    public function tieneDificultades(): bool
    {
        return $this->motivosDificultades() !== [];
    }

    /**
     * @return list<string>
     */
    public function motivosDificultades(): array
    {
        $motivos = [];

        if ($this->tieneNotasBajoElMinimo()) {
            $motivos[] = 'Calificaciones por debajo del mínimo de aprobación';
        }

        if ($this->tieneTendenciaALaBaja()) {
            $motivos[] = 'Tendencia a la baja en los insumos';
        }

        if ($this->tieneFaltasReiteradas()) {
            $motivos[] = 'Inasistencias reiteradas';
        }

        return $motivos;
    }

    /**
     * @return array<string, mixed>
     */
    public function toResumenRiesgo(): array
    {
        return [
            'estudiante' => $this->estudiante,
            'motivos' => $this->motivosDificultades(),
            'notas_insumo' => array_slice($this->notasInsumo, -6),
            'calificaciones_periodo' => $this->calificacionesPeriodo,
            'resumen_asistencia' => $this->resumenAsistencia(),
        ];
    }

    public function promedioNotas(): ?float
    {
        $delContexto = $this->notasNumericas(true);

        $notas = $delContexto !== [] ? $delContexto : $this->notasNumericas(false);

        if ($notas === []) {
            return $this->notaPeriodoDelContexto();
        }

        return round(array_sum($notas) / count($notas), 2);
    }

    /**
     * @return array{estudiante: string, nota: float|null, etiqueta: string, porcentaje: float, bajoMinimo: bool}
     */
    public function toPuntoGrafico(float $notaMaxima): array
    {
        $nota = $this->promedioNotas();
        $maxima = $notaMaxima > 0 ? $notaMaxima : 10.0;

        return [
            'estudiante' => $this->estudiante,
            'nota' => $nota,
            'etiqueta' => $nota === null ? 'Sin nota' : number_format($nota, 2, '.', ''),
            'porcentaje' => $nota === null ? 0.0 : round(min(100, max(0, ($nota / $maxima) * 100)), 2),
            'bajoMinimo' => $nota !== null && $nota < $this->notaMinimaAprobacion,
        ];
    }

    private function tieneNotasBajoElMinimo(): bool
    {
        foreach ([...$this->notasInsumo, ...$this->calificacionesPeriodo] as $fila) {
            $nota = $fila['nota'] ?? null;

            if (is_numeric($nota) && (float) $nota < $this->notaMinimaAprobacion) {
                return true;
            }
        }

        return false;
    }

    private function tieneTendenciaALaBaja(): bool
    {
        $notas = [];

        foreach ($this->notasInsumo as $fila) {
            if (is_numeric($fila['nota'] ?? null)) {
                $notas[] = (float) $fila['nota'];
            }
        }

        if (count($notas) < 2) {
            return false;
        }

        $umbral = $this->notaMinimaAprobacion >= 7 ? 1.0 : 0.5;

        return $notas[array_key_last($notas)] <= $notas[array_key_first($notas)] - $umbral;
    }

    private function tieneFaltasReiteradas(): bool
    {
        $resumen = $this->resumenAsistencia();

        if ($resumen['faltas'] >= 2) {
            return true;
        }

        return $resumen['total'] >= 5 && ($resumen['faltas'] / $resumen['total']) >= 0.2;
    }

    /**
     * @return list<float>
     */
    private function notasNumericas(bool $soloContexto): array
    {
        $notas = [];

        foreach ($this->notasInsumo as $fila) {
            if (! is_numeric($fila['nota'] ?? null)) {
                continue;
            }

            if ($soloContexto && ! $this->insumoCoincideConContexto($fila)) {
                continue;
            }

            $notas[] = (float) $fila['nota'];
        }

        return $notas;
    }

    /**
     * @param  array{ciclo?: string, parcial?: int}  $fila
     */
    private function insumoCoincideConContexto(array $fila): bool
    {
        $ciclo = (string) ($fila['ciclo'] ?? '');
        $parcial = (int) ($fila['parcial'] ?? 0);

        $mismoCiclo = $ciclo === '' || $ciclo === $this->ciclo;
        $mismoParcial = $parcial === 0 || $parcial === $this->parcial;

        return $mismoCiclo && $mismoParcial;
    }

    private function notaPeriodoDelContexto(): ?float
    {
        foreach ($this->calificacionesPeriodo as $fila) {
            if (! is_numeric($fila['nota'] ?? null)) {
                continue;
            }

            if ((int) ($fila['parcial'] ?? 0) !== $this->parcial) {
                continue;
            }

            return (float) $fila['nota'];
        }

        return null;
    }
}
