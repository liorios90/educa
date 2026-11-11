<?php

namespace App\Enums;

/**
 * Escala del Reglamento General a la LOEI y del Acuerdo MINEDEC-MINEDEC-2026-00082-A.
 *
 * Básica Elemental usa A, EP e I, con equivalencia sobre 10.
 * Inicial y Preparatoria usan letras de A+ a E-, sin promedio numérico.
 * NE significa que la destreza no se trabajó en el periodo.
 */
enum CalificacionDestreza: string
{
    case Alcanzado = 'A';
    case EnProceso = 'EP';
    case Iniciado = 'I';
    case NoEvaluado = 'NE';
    case APlus = 'A+';
    case AMinus = 'A-';
    case BPlus = 'B+';
    case BMinus = 'B-';
    case CPlus = 'C+';
    case CMinus = 'C-';
    case DPlus = 'D+';
    case DMinus = 'D-';
    case EPlus = 'E+';
    case EMinus = 'E-';

    public function etiqueta(): string
    {
        return $this->banda();
    }

    public function banda(): string
    {
        return match ($this) {
            self::Alcanzado, self::APlus, self::AMinus => 'Destreza o aprendizaje alcanzado',
            self::EnProceso, self::BPlus, self::BMinus, self::CPlus, self::CMinus => 'Destreza o aprendizaje en proceso de desarrollo',
            self::Iniciado, self::DPlus, self::DMinus, self::EPlus, self::EMinus => 'Destreza o aprendizaje iniciado',
            self::NoEvaluado => 'No evaluado',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::Alcanzado => 'El estudiante evidencia el logro de las destrezas y aprendizajes previstos en el tiempo planificado.',
            self::EnProceso => 'El estudiante está en proceso de alcanzar las destrezas y aprendizajes previstos y requiere acompañamiento del docente y de la familia.',
            self::Iniciado => 'El estudiante está empezando a desarrollar las destrezas y aprendizajes previstos y necesita mayor tiempo de acompañamiento.',
            self::NoEvaluado => 'Esta destreza no se trabajó o no se evaluó en el periodo.',
            self::APlus => 'Logra la destreza con autonomía, en situaciones simples y nuevas.',
            self::AMinus => 'Logra la destreza en las actividades cotidianas, solo y con otros.',
            self::BPlus => 'La aplica en situaciones conocidas y colabora con el grupo.',
            self::BMinus => 'La realiza casi siempre solo; a veces necesita una indicación.',
            self::CPlus => 'Va en camino y necesita el apoyo del docente.',
            self::CMinus => 'Resuelve tareas simples y todavía requiere mediación.',
            self::DPlus => 'La intenta cuando el docente lo guía.',
            self::DMinus => 'Le cuesta completar la tarea y necesita orientación frecuente.',
            self::EPlus => 'Está empezando y requiere intervención permanente.',
            self::EMinus => 'Muestra un inicio muy temprano y necesita apoyo continuo.',
        };
    }

    public function permitidaEnAmbito(): bool
    {
        return in_array($this, self::casosAmbito(), true);
    }

    public function permitidaEnElemental(): bool
    {
        return in_array($this, [self::Alcanzado, self::EnProceso, self::Iniciado, self::NoEvaluado], true);
    }

    /**
     * C+ o menos activa el acompañamiento del acuerdo: iniciado y en proceso todavía frágil.
     */
    public function requiereAcompanamiento(): bool
    {
        return in_array($this, [
            self::CPlus,
            self::CMinus,
            self::DPlus,
            self::DMinus,
            self::EPlus,
            self::EMinus,
            self::Iniciado,
        ], true);
    }

    public function notaElemental(): ?float
    {
        return match ($this) {
            self::Alcanzado => 9.5,
            self::EnProceso => 8.0,
            self::Iniciado => 5.0,
            default => null,
        };
    }

    public static function desdeNotaElemental(float $nota): self
    {
        return match (true) {
            $nota >= 9.0 => self::Alcanzado,
            $nota >= 7.0 => self::EnProceso,
            default => self::Iniciado,
        };
    }

    /**
     * @param  list<self|null>  $escalas
     */
    public static function masBajo(array $escalas): ?self
    {
        $evaluadas = [];
        $noEvaluado = false;

        foreach ($escalas as $escala) {
            if (! $escala instanceof self) {
                continue;
            }

            if ($escala === self::NoEvaluado) {
                $noEvaluado = true;

                continue;
            }

            $evaluadas[] = $escala;
        }

        if ($evaluadas === []) {
            return $noEvaluado ? self::NoEvaluado : null;
        }

        usort($evaluadas, fn (self $izquierda, self $derecha): int => $izquierda->prioridad() <=> $derecha->prioridad());

        return $evaluadas[0];
    }

    /**
     * @param  list<self>  $escalas
     */
    public static function ultimaEvaluada(array $escalas): ?self
    {
        $ultima = null;

        foreach ($escalas as $escala) {
            if ($escala !== self::NoEvaluado) {
                $ultima = $escala;
            }
        }

        return $ultima ?? (in_array(self::NoEvaluado, $escalas, true) ? self::NoEvaluado : null);
    }

    /**
     * @return list<array{codigo: string, etiqueta: string, descripcion: string}>
     */
    public static function opciones(): array
    {
        return self::lista([self::Alcanzado, self::EnProceso, self::Iniciado, self::NoEvaluado]);
    }

    /**
     * @return list<array{codigo: string, etiqueta: string, descripcion: string}>
     */
    public static function opcionesAmbito(): array
    {
        return self::lista(self::casosAmbito());
    }

    /**
     * @param  list<self>  $casos
     * @return list<array{codigo: string, etiqueta: string, descripcion: string}>
     */
    private static function lista(array $casos): array
    {
        return array_map(fn (self $escala): array => [
            'codigo' => $escala->value,
            'etiqueta' => $escala->etiqueta(),
            'descripcion' => $escala->descripcion(),
        ], $casos);
    }

    /**
     * @return list<self>
     */
    private static function casosAmbito(): array
    {
        return [
            self::APlus,
            self::AMinus,
            self::BPlus,
            self::BMinus,
            self::CPlus,
            self::CMinus,
            self::DPlus,
            self::DMinus,
            self::EPlus,
            self::EMinus,
            self::NoEvaluado,
        ];
    }

    private function prioridad(): int
    {
        return match ($this) {
            self::EMinus => 1,
            self::EPlus => 2,
            self::DMinus => 3,
            self::DPlus => 4,
            self::Iniciado => 5,
            self::CMinus => 6,
            self::CPlus => 7,
            self::EnProceso => 8,
            self::BMinus => 9,
            self::BPlus => 10,
            self::AMinus => 11,
            self::Alcanzado => 12,
            self::APlus => 13,
            self::NoEvaluado => 14,
        };
    }
}
