<?php

namespace App\Auth;

use App\Enums\ModoAsistencia;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoPeriodoCiclo;

class ContextoAsistencia
{
    public function __construct(
        public EstablecimientoAula $aula,
        public ModoAsistencia $modo,
        public ?EstablecimientoAsignatura $asignatura = null,
        public ?EstablecimientoPeriodoCiclo $ciclo = null,
        public ?int $parcialNumero = null,
    ) {}

    public function etiqueta(): string
    {
        if ($this->modo->esJornada()) {
            return trim($this->aula->etiqueta().' · Asistencia del día');
        }

        $partes = [
            $this->aula->etiqueta(),
            $this->asignatura?->nombre() ?? '',
            $this->ciclo?->nombre ?? '',
        ];

        if ((int) $this->parcialNumero > 0) {
            $partes[] = 'Parcial '.$this->parcialNumero;
        }

        return trim(implode(' · ', array_filter($partes, fn (string $parte): bool => $parte !== '')));
    }
}
