<?php

namespace App\Services\Documentos;

use App\Enums\EscalaCalificacion;
use App\Enums\TipoAsignatura;

readonly class FilaDocumentoAcademico
{
    /**
     * @param  list<array{ciclo_orden: int, parcial_numero: int, nota: string}>  $notas
     */
    public function __construct(
        public int $establecimientoAsignaturaId,
        public string $nombre,
        public ?string $area,
        public TipoAsignatura $tipoAsignatura,
        public EscalaCalificacion $escala,
        public array $notas,
    ) {}

    public function notaDe(int $cicloOrden, int $parcialNumero): ?string
    {
        foreach ($this->notas as $nota) {
            if ($nota['ciclo_orden'] === $cicloOrden && $nota['parcial_numero'] === $parcialNumero) {
                return $nota['nota'];
            }
        }

        return null;
    }
}
