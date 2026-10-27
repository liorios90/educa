<?php

namespace App\Enums;

enum CalificacionComportamiento: string
{
    case MuySatisfactorio = 'A';
    case Satisfactorio = 'B';
    case PocoSatisfactorio = 'C';
    case Mejorable = 'D';
    case Insatisfactorio = 'E';

    public function label(): string
    {
        return match ($this) {
            self::MuySatisfactorio => 'Muy satisfactorio',
            self::Satisfactorio => 'Satisfactorio',
            self::PocoSatisfactorio => 'Poco satisfactorio',
            self::Mejorable => 'Mejorable',
            self::Insatisfactorio => 'Insatisfactorio',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::MuySatisfactorio => 'Lidera el cumplimiento de los compromisos establecidos para la sana convivencia social.',
            self::Satisfactorio => 'Cumple con los compromisos establecidos para la sana convivencia social.',
            self::PocoSatisfactorio => 'Falla ocasionalmente en el cumplimiento de los compromisos establecidos para la sana convivencia social.',
            self::Mejorable => 'Falla reiteradamente en el cumplimiento de los compromisos establecidos para la sana convivencia social.',
            self::Insatisfactorio => 'No cumple con los compromisos establecidos para la sana convivencia social.',
        };
    }

    public function exigeObservacion(): bool
    {
        return match ($this) {
            self::PocoSatisfactorio, self::Mejorable, self::Insatisfactorio => true,
            default => false,
        };
    }
}
