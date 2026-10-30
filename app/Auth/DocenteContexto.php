<?php

namespace App\Auth;

use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\EstablecimientoPeriodoCicloParcial;

class DocenteContexto
{
    public const MENSAJE_PERIODO_CERRADO = 'Apertura el periodo para ingresar calificaciones.';

    public const MENSAJE_PARCIAL_CERRADO = 'Este parcial está cerrado. Puedes consultar las calificaciones, pero no modificarlas.';

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

    public function permiteIngresarCalificaciones(): bool
    {
        $this->aula->loadMissing('periodo');

        return $this->aula->periodo?->estaAbierto() === true;
    }

    public function parcialEstaCerrado(): bool
    {
        if ($this->parcialNumero < 1) {
            return false;
        }

        $this->ciclo->loadMissing('parciales');

        return $this->ciclo->parciales->contains(
            fn (EstablecimientoPeriodoCicloParcial $parcial): bool => (int) $parcial->numero === $this->parcialNumero
                && $parcial->cerrado_en !== null,
        );
    }
}
