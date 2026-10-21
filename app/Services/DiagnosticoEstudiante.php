<?php

namespace App\Services;

readonly class DiagnosticoEstudiante
{
    /**
     * @param  list<string>  $tendencias
     * @param  list<string>  $alertas
     * @param  list<string>  $recomendaciones
     */
    public function __construct(
        public string $estudiante,
        public string $resumen,
        public string $riesgoReprobar,
        public array $tendencias,
        public array $alertas,
        public array $recomendaciones,
        public int $tokens = 0,
    ) {}

    /**
     * @return array{estudiante: string, resumen: string, riesgo_reprobar: string, tendencias: list<string>, alertas: list<string>, recomendaciones: list<string>}
     */
    public function toArray(): array
    {
        return [
            'estudiante' => $this->estudiante,
            'resumen' => $this->resumen,
            'riesgo_reprobar' => $this->riesgoReprobar,
            'tendencias' => $this->tendencias,
            'alertas' => $this->alertas,
            'recomendaciones' => $this->recomendaciones,
        ];
    }
}
