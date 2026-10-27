<?php

namespace App\Enums;

enum MotivoJustificacionAsistencia: string
{
    case CertificadoMedico = 'certificado_medico';
    case CalamidadDomestica = 'calamidad_domestica';
    case RepresentacionInstitucional = 'representacion_institucional';
    case CitacionAutoridad = 'citacion_autoridad';

    public function label(): string
    {
        return match ($this) {
            self::CertificadoMedico => 'Certificado médico',
            self::CalamidadDomestica => 'Calamidad doméstica',
            self::RepresentacionInstitucional => 'Representación del plantel',
            self::CitacionAutoridad => 'Citación de autoridad',
        };
    }
}
