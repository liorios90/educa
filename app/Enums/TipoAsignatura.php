<?php

namespace App\Enums;

enum TipoAsignatura: string
{
    case TroncoComun = 'tronco_comun';
    case Pedagogica = 'pedagogica';
    case Optativa = 'optativa';
    case Extra = 'extra';
    case ProgramaInternacional = 'programa_internacional';

    public function label(): string
    {
        return match ($this) {
            self::TroncoComun => 'Tronco común',
            self::Pedagogica => 'Pedagógica',
            self::Optativa => 'Optativa',
            self::Extra => 'Extra',
            self::ProgramaInternacional => 'Programa internacional',
        };
    }

    public function exigeInscripcionIndividual(): bool
    {
        return $this === self::Optativa || $this === self::ProgramaInternacional;
    }
}
