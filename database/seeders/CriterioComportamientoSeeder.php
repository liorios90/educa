<?php

namespace Database\Seeders;

use App\Enums\TipoCriterioComportamiento;
use App\Models\Sys_CriterioComportamiento;
use Illuminate\Database\Seeder;

class CriterioComportamientoSeeder extends Seeder
{
    /**
     * Habilidades priorizadas para el acompañamiento socioemocional.
     * El Acuerdo MINEDEC-MINEDEC-2026-00082-A remite a estos lineamientos.
     */
    public function run(): void
    {
        foreach ($this->criterios() as $fila) {
            $criterio = Sys_CriterioComportamiento::query()->firstOrNew([
                'codigo' => $fila['codigo'],
            ]);
            $nuevo = ! $criterio->exists;

            $criterio->descripcion = $fila['descripcion'];
            $criterio->tipo = $fila['tipo'];

            if ($nuevo) {
                $criterio->activo = true;
                $criterio->obligatorio = $fila['obligatorio'];
                $criterio->usuario = 'MINEDEC';
            }

            $criterio->save();
        }
    }

    /**
     * @return list<array{codigo: string, descripcion: string, tipo: TipoCriterioComportamiento, obligatorio: bool}>
     */
    private function criterios(): array
    {
        return [
            [
                'codigo' => 'COG-01',
                'descripcion' => 'Autoconocimiento. Reconocerse como una persona única, identificando cualidades y características particulares.',
                'tipo' => TipoCriterioComportamiento::Cognitiva,
                'obligatorio' => true,
            ],
            [
                'codigo' => 'COG-02',
                'descripcion' => 'Pensamiento crítico. Analizar, evaluar y cuestionar de manera reflexiva ideas, información, argumentos y situaciones.',
                'tipo' => TipoCriterioComportamiento::Cognitiva,
                'obligatorio' => true,
            ],
            [
                'codigo' => 'COG-03',
                'descripcion' => 'Toma de decisiones. Analizar situaciones considerando necesidades, valores, motivaciones, influencias y consecuencias para sí y para otras personas.',
                'tipo' => TipoCriterioComportamiento::Cognitiva,
                'obligatorio' => true,
            ],
            [
                'codigo' => 'COG-04',
                'descripcion' => 'Manejo de problemas. Identificar, analizar y resolver problemas de manera efectiva.',
                'tipo' => TipoCriterioComportamiento::Cognitiva,
                'obligatorio' => false,
            ],
            [
                'codigo' => 'COG-05',
                'descripcion' => 'Pensamiento creativo. Generar ideas nuevas, originales e innovadoras para resolver problemas o explorar oportunidades.',
                'tipo' => TipoCriterioComportamiento::Cognitiva,
                'obligatorio' => false,
            ],
            [
                'codigo' => 'SOC-01',
                'descripcion' => 'Manejo de conflictos. Identificar desacuerdos para prevenir y resolver conflictos de manera constructiva.',
                'tipo' => TipoCriterioComportamiento::Social,
                'obligatorio' => true,
            ],
            [
                'codigo' => 'SOC-02',
                'descripcion' => 'Trabajo en equipo. Colaborar y trabajar de manera efectiva en grupos diversos.',
                'tipo' => TipoCriterioComportamiento::Social,
                'obligatorio' => false,
            ],
            [
                'codigo' => 'SOC-03',
                'descripcion' => 'Conciencia social. Comprender las perspectivas de las demás personas en contextos, culturas y situaciones diversas.',
                'tipo' => TipoCriterioComportamiento::Social,
                'obligatorio' => false,
            ],
            [
                'codigo' => 'SOC-04',
                'descripcion' => 'Pensamiento ético. Tomar decisiones basadas en principios éticos y actuar de manera responsable y respetuosa.',
                'tipo' => TipoCriterioComportamiento::Social,
                'obligatorio' => false,
            ],
            [
                'codigo' => 'SOC-05',
                'descripcion' => 'Empatía. Comprender y compartir los sentimientos de las demás personas, viendo las situaciones desde su perspectiva.',
                'tipo' => TipoCriterioComportamiento::Social,
                'obligatorio' => false,
            ],
            [
                'codigo' => 'SOC-06',
                'descripcion' => 'Relaciones interpersonales. Relacionarse y comunicarse de manera efectiva con las demás personas.',
                'tipo' => TipoCriterioComportamiento::Social,
                'obligatorio' => false,
            ],
            [
                'codigo' => 'SOC-07',
                'descripcion' => 'Comunicación efectiva y asertiva. Expresar pensamientos, sentimientos, opiniones y necesidades de manera clara, directa y respetuosa.',
                'tipo' => TipoCriterioComportamiento::Social,
                'obligatorio' => false,
            ],
            [
                'codigo' => 'SOC-08',
                'descripcion' => 'Ciudadanía global. Comprender y valorar la diversidad cultural, promover la justicia social y contribuir al bienestar colectivo.',
                'tipo' => TipoCriterioComportamiento::Social,
                'obligatorio' => false,
            ],
            [
                'codigo' => 'EMO-01',
                'descripcion' => 'Manejo de emociones y sentimientos. Reconocer, regular y expresar de manera adecuada las emociones propias y de las demás personas.',
                'tipo' => TipoCriterioComportamiento::Emocional,
                'obligatorio' => false,
            ],
            [
                'codigo' => 'EMO-02',
                'descripcion' => 'Manejo de estrés y tensiones. Emplear estrategias para reducir el estrés y las tensiones de la vida cotidiana.',
                'tipo' => TipoCriterioComportamiento::Emocional,
                'obligatorio' => false,
            ],
        ];
    }
}
