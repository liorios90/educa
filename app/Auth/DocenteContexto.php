<?php

namespace App\Auth;

use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoPeriodoCiclo;

class DocenteContexto
{
    public function __construct(
        public EstablecimientoAula $aula,
        public EstablecimientoAsignatura $asignatura,
        public EstablecimientoPeriodoCiclo $ciclo,
        public int $parcialNumero,
    ) {}

    public function etiqueta(): string
    {
        return trim(implode(' · ', [
            $this->aula->etiqueta(),
            $this->asignatura->nombre(),
            $this->ciclo->nombre,
            'Parcial '.$this->parcialNumero,
        ]));
    }
}
