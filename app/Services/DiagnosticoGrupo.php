<?php

namespace App\Services;

readonly class DiagnosticoGrupo
{
    /**
     * @param  list<array{estudiante: string, riesgo_reprobar: string, problemas: list<string>, recomendaciones: list<string>}>  $estudiantes
     * @param  list<string>  $recomendaciones
     */
    public function __construct(
        public string $aula,
        public string $asignatura,
        public string $resumen,
        public array $estudiantes,
        public array $recomendaciones,
        public int $tokens = 0,
    ) {}

    /**
     * @return array{aula: string, asignatura: string, resumen: string, estudiantes: list<array{estudiante: string, riesgo_reprobar: string, problemas: list<string>, recomendaciones: list<string>}>, recomendaciones: list<string>}
     */
    public function toArray(): array
    {
        return [
            'aula' => $this->aula,
            'asignatura' => $this->asignatura,
            'resumen' => $this->resumen,
            'estudiantes' => $this->estudiantes,
            'recomendaciones' => $this->recomendaciones,
        ];
    }
}
