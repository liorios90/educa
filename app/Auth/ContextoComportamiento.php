<?php

namespace App\Auth;

use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoPeriodoCiclo;

class ContextoComportamiento
{
    public function __construct(
        public EstablecimientoAula $aula,
        public EstablecimientoAsignatura $asignatura,
        public EstablecimientoPeriodoCiclo $ciclo,
    ) {}

    public function etiqueta(): string
    {
        return trim($this->aula->etiqueta().' · '.$this->asignatura->nombre().' · '.$this->ciclo->nombre);
    }
}
