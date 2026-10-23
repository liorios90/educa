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
        $partes = [
            $this->aula->etiqueta(),
            $this->asignatura->nombre(),
            $this->ciclo->nombre,
        ];

        if ($this->parcialNumero > 0) {
            $partes[] = 'Parcial '.$this->parcialNumero;
        }

        return trim(implode(' · ', $partes));
    }
}
