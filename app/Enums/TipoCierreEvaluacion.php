<?php

namespace App\Enums;

enum TipoCierreEvaluacion: string
{
    case Examen = 'examen';
    case Proyecto = 'proyecto';
    case ExamenFinal = 'examen_final';
    case ProyectoFinal = 'proyecto_final';

    public function label(): string
    {
        return match ($this) {
            self::Examen => 'Examen',
            self::Proyecto => 'Proyecto',
            self::ExamenFinal => 'Examen final',
            self::ProyectoFinal => 'Proyecto final',
        };
    }

    public function labelPlural(): string
    {
        return match ($this) {
            self::Examen => 'Exámenes',
            self::Proyecto => 'Proyectos',
            self::ExamenFinal => 'Exámenes finales',
            self::ProyectoFinal => 'Proyectos finales',
        };
    }

    public function ruta(): string
    {
        return match ($this) {
            self::Examen => 'examenes',
            self::Proyecto => 'proyectos',
            self::ExamenFinal => 'examenes-finales',
            self::ProyectoFinal => 'proyectos-finales',
        };
    }

    public function route(string $accion): string
    {
        return 'Docente.'.$this->ruta().'.'.$accion;
    }

    public function esFinal(): bool
    {
        return $this === self::ExamenFinal || $this === self::ProyectoFinal;
    }

    public function requiereCiclo(): bool
    {
        return ! $this->esFinal();
    }

    public function mensajeSinConfiguracion(): string
    {
        $ambito = $this->esFinal() ? 'Esta aula' : 'Este ciclo';

        return $ambito.' no tiene '.$this->label().' configurado.';
    }

    public static function fromRuta(string $tipo): self
    {
        return match ($tipo) {
            'examenes' => self::Examen,
            'proyectos' => self::Proyecto,
            'examenes-finales' => self::ExamenFinal,
            'proyectos-finales' => self::ProyectoFinal,
            default => abort(404),
        };
    }
}
