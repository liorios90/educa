<?php

namespace App\Auth;

use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoPeriodoCiclo;

class ContextoComportamiento
{
    public function __construct(
        public EstablecimientoAula $aula,
        public EstablecimientoPeriodoCiclo $ciclo,
    ) {}

    public function etiqueta(): string
    {
        return trim($this->aula->etiqueta().' · '.$this->ciclo->nombre);
    }
}
