<?php

namespace App\Services;

use App\Models\Establecimiento;

class DiagnosticoEstudianteService
{
    public const INSTRUCCION_SISTEMA = 'Eres un asesor académico de un colegio ecuatoriano que sigue el MINEDUC. Analizas historiales de notas y asistencia. Respondes únicamente con JSON válido, sin markdown ni texto extra.';

    public function __construct(
        private GeminiService $gemini,
    ) {}

    public function analizar(HistorialAcademico $historial, Establecimiento $establecimiento): DiagnosticoEstudiante
    {
        $respuesta = $this->gemini->generate(
            $this->prompt($historial),
            $establecimiento,
            self::INSTRUCCION_SISTEMA,
        );

        return $this->parsear($respuesta->texto, $historial->estudiante, $respuesta->tokens);
    }

    /**
     * @param  list<HistorialAcademico>  $historiales
     */
    public function analizarGrupo(array $historiales, Establecimiento $establecimiento): DiagnosticoGrupo
    {
        $respuesta = $this->gemini->generate(
            $this->promptGrupo($historiales),
            $establecimiento,
            self::INSTRUCCION_SISTEMA,
        );

        $primero = $historiales[0];

        return $this->parsearGrupo($respuesta->texto, $primero->aula, $primero->asignatura, $respuesta->tokens);
    }

    public function prompt(HistorialAcademico $historial): string
    {
        $payload = json_encode(
            $historial->toPromptPayload(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
        );

        return <<<PROMPT
Analiza el historial académico y de asistencia del estudiante.

En la escala nacional (sobre 10) aprueba con 7.00 o más. En Bachillerato Internacional (1 a 7) aprueba con 4 o más.
Identifica tendencias a la baja, inasistencias reiteradas y riesgo de reprobar.
Devuelve solo este JSON:
{"resumen":"texto breve","riesgo_reprobar":"bajo|medio|alto","tendencias":["..."],"alertas":["..."],"recomendaciones":["..."]}

Datos:
{$payload}
PROMPT;
    }

    /**
     * @param  list<HistorialAcademico>  $historiales
     */
    public function promptGrupo(array $historiales): string
    {
        $primero = $historiales[0];
        $payload = json_encode([
            'contexto' => [
                'aula' => $primero->aula,
                'asignatura' => $primero->asignatura,
                'periodo' => $primero->periodo,
                'ciclo' => $primero->ciclo,
                'parcial' => $primero->parcial,
                'escala' => $primero->escala,
                'nota_minima_aprobacion' => $primero->notaMinimaAprobacion,
            ],
            'estudiantes_con_dificultades' => array_map(
                fn (HistorialAcademico $historial): array => $historial->toResumenRiesgo(),
                $historiales,
            ),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return <<<PROMPT
Estos estudiantes del mismo paralelo ya muestran señales de dificultad (notas bajo el mínimo, tendencia a la baja o inasistencias).
En la escala nacional (sobre 10) aprueba con 7.00 o más. En Bachillerato Internacional (1 a 7) aprueba con 4 o más.
Para cada uno indica el riesgo de reprobar y una o dos recomendaciones concretas para el docente.
Agrega también recomendaciones para el grupo.
Devuelve solo este JSON:
{"resumen":"texto breve del grupo","estudiantes":[{"estudiante":"nombre","riesgo_reprobar":"bajo|medio|alto","problemas":["..."],"recomendaciones":["..."]}],"recomendaciones":["..."]}

Datos:
{$payload}
PROMPT;
    }

    private function parsear(string $texto, string $estudiante, int $tokens): DiagnosticoEstudiante
    {
        $datos = json_decode($this->textoJson($texto), true);

        if (! is_array($datos)) {
            return new DiagnosticoEstudiante(
                estudiante: $estudiante,
                resumen: $texto,
                riesgoReprobar: 'desconocido',
                tendencias: [],
                alertas: [],
                recomendaciones: [],
                tokens: $tokens,
            );
        }

        return new DiagnosticoEstudiante(
            estudiante: $estudiante,
            resumen: is_string($datos['resumen'] ?? null) ? $datos['resumen'] : $texto,
            riesgoReprobar: $this->riesgoDe($datos['riesgo_reprobar'] ?? null),
            tendencias: $this->listaDe($datos['tendencias'] ?? []),
            alertas: $this->listaDe($datos['alertas'] ?? []),
            recomendaciones: $this->listaDe($datos['recomendaciones'] ?? []),
            tokens: $tokens,
        );
    }

    private function parsearGrupo(string $texto, string $aula, string $asignatura, int $tokens): DiagnosticoGrupo
    {
        $datos = json_decode($this->textoJson($texto), true);

        if (! is_array($datos)) {
            return new DiagnosticoGrupo(
                aula: $aula,
                asignatura: $asignatura,
                resumen: $texto,
                estudiantes: [],
                recomendaciones: [],
                tokens: $tokens,
            );
        }

        return new DiagnosticoGrupo(
            aula: is_string($datos['aula'] ?? null) ? $datos['aula'] : $aula,
            asignatura: is_string($datos['asignatura'] ?? null) ? $datos['asignatura'] : $asignatura,
            resumen: is_string($datos['resumen'] ?? null) ? $datos['resumen'] : $texto,
            estudiantes: $this->estudiantesDe($datos['estudiantes'] ?? []),
            recomendaciones: $this->listaDe($datos['recomendaciones'] ?? []),
            tokens: $tokens,
        );
    }

    /**
     * @return list<array{estudiante: string, riesgo_reprobar: string, problemas: list<string>, recomendaciones: list<string>}>
     */
    private function estudiantesDe(mixed $valor): array
    {
        if (! is_array($valor)) {
            return [];
        }

        $estudiantes = [];

        foreach ($valor as $fila) {
            if (! is_array($fila)) {
                continue;
            }

            $nombre = is_string($fila['estudiante'] ?? null) ? trim($fila['estudiante']) : '';

            if ($nombre === '') {
                continue;
            }

            $estudiantes[] = [
                'estudiante' => $nombre,
                'riesgo_reprobar' => $this->riesgoDe($fila['riesgo_reprobar'] ?? null),
                'problemas' => $this->listaDe($fila['problemas'] ?? []),
                'recomendaciones' => $this->listaDe($fila['recomendaciones'] ?? []),
            ];
        }

        return $estudiantes;
    }

    private function riesgoDe(mixed $valor): string
    {
        $riesgo = strtolower(trim((string) $valor));

        if (! in_array($riesgo, ['bajo', 'medio', 'alto'], true)) {
            return 'desconocido';
        }

        return $riesgo;
    }

    private function textoJson(string $texto): string
    {
        $limpio = trim($texto);

        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $limpio, $bloques) === 1) {
            return trim($bloques[1]);
        }

        return $limpio;
    }

    /**
     * @return list<string>
     */
    private function listaDe(mixed $valor): array
    {
        if (! is_array($valor)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn (mixed $item): string => is_scalar($item) ? trim((string) $item) : '', $valor),
            fn (string $item): bool => $item !== '',
        ));
    }
}
