<?php

namespace App\Services\Documentos;

readonly class ResultadoDocumentoAcademico
{
    /**
     * @param  list<FilaDocumentoAcademico>  $filas
     */
    public function __construct(
        public int $matriculaId,
        public int $aulaId,
        public string $paralelo,
        public int $gradoId,
        public string $grado,
        public array $filas,
    ) {}

    /**
     * @return list<string>
     */
    public function nombres(): array
    {
        return array_map(
            fn (FilaDocumentoAcademico $fila): string => $fila->nombre,
            $this->filas,
        );
    }

    public function fila(string $nombre): ?FilaDocumentoAcademico
    {
        foreach ($this->filas as $fila) {
            if ($fila->nombre === $nombre) {
                return $fila;
            }
        }

        return null;
    }
}
