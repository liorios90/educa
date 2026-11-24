<?php

namespace Database\Seeders;

use App\Enums\TipoCalificacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoPeriodo;
use App\Models\TipoInsumo;
use App\Services\AcompanamientoCivica;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoCivicaSisEducaSeeder extends Seeder
{
    private const CODIGO_AMIE = '01AS01061';

    private const ASIGNATURA = 'Cívica y Acompañamiento Integral en el Aula';

    /**
     * Cívica se evalúa cualitativamente y no entra al promedio cuantitativo.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $establecimiento = Establecimiento::query()
                ->where('codigo_amie', self::CODIGO_AMIE)
                ->firstOrFail();

            EstablecimientoAsignatura::query()
                ->where('establecimiento_id', $establecimiento->id)
                ->whereHas('asignatura', fn ($query) => $query->where('nombre', self::ASIGNATURA))
                ->update([
                    'tipo_calificacion' => TipoCalificacion::Cualitativa->value,
                    'califica_por_destrezas' => true,
                ]);

            $periodos = EstablecimientoPeriodo::query()
                ->where('establecimiento_id', $establecimiento->id)
                ->get();

            foreach ($periodos as $periodo) {
                $tipo = TipoInsumo::query()->updateOrCreate(
                    [
                        'establecimiento_periodo_id' => $periodo->id,
                        'nombre' => self::ASIGNATURA,
                    ],
                    [
                        'descripcion' => 'Evaluación cualitativa del periodo pedagógico. Acuerdo MINEDEC-MINEDEC-2026-00082-A, artículo 9. No forma parte del promedio ni es requisito de promoción.',
                        'tipo_calificacion' => TipoCalificacion::Cualitativa->value,
                        'activo' => true,
                    ],
                );

                foreach ($this->escala() as $opcion) {
                    $tipo->detalles()->updateOrCreate(
                        ['valor' => $opcion['valor']],
                        [
                            'nombre' => $opcion['nombre'],
                            'descripcion' => $opcion['descripcion'],
                            'porcentaje' => null,
                            'activo' => true,
                        ],
                    );
                }

                $this->asignar($periodo, $tipo);
            }

            app(AcompanamientoCivica::class)->guardar($establecimiento, [
                'CIV.03',
                'CIV.06',
                'CIV.09',
                'CIV.12',
                'CIV.14',
            ]);
        });
    }

    private function asignar(EstablecimientoPeriodo $periodo, TipoInsumo $tipo): void
    {
        $aulas = EstablecimientoAula::query()
            ->where('establecimiento_periodo_id', $periodo->id)
            ->with('establecimientoGrado')
            ->get();
        $mallas = EstablecimientoAsignatura::query()
            ->where('establecimiento_id', $periodo->establecimiento_id)
            ->whereHas('asignatura', fn ($query) => $query->where('nombre', self::ASIGNATURA))
            ->get();

        foreach ($aulas as $aula) {
            $malla = $mallas->first(fn (EstablecimientoAsignatura $asignatura): bool => $asignatura->grado_id === $aula->establecimientoGrado?->grado_id
                && $asignatura->establecimiento_modalidad_jornada_id === $aula->establecimiento_modalidad_jornada_id);

            if ($malla === null) {
                continue;
            }

            DB::table('establecimiento_aula_asignatura_tipo_insumos')->updateOrInsert(
                [
                    'establecimiento_aula_id' => $aula->id,
                    'establecimiento_asignatura_id' => $malla->id,
                ],
                [
                    'tipo_insumo_id' => $tipo->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    /**
     * Escala del Instructivo de Evaluación Estudiantil, tabla 2, aplicable a este periodo pedagógico.
     *
     * @return list<array{valor: string, nombre: string, descripcion: string}>
     */
    private function escala(): array
    {
        return [
            [
                'valor' => 'A',
                'nombre' => 'Destreza o aprendizaje alcanzado',
                'descripcion' => 'El estudiante evidencia el logro de las destrezas y aprendizajes previstos en el tiempo planificado.',
            ],
            [
                'valor' => 'EP',
                'nombre' => 'Destreza o aprendizaje en proceso de desarrollo',
                'descripcion' => 'El estudiante está en proceso de alcanzar las destrezas y aprendizajes previstos para lo cual requiere del acompañamiento durante el tiempo necesario, del docente y la madre, el padre de familia o representante legal.',
            ],
            [
                'valor' => 'I',
                'nombre' => 'Destreza o aprendizaje iniciado',
                'descripcion' => 'El estudiante está empezando a desarrollar las destrezas y aprendizajes previstos y necesita mayor tiempo de acompañamiento e intervención del docente y la madre, el padre de familia o representante legal, de acuerdo con su ritmo de aprendizaje.',
            ],
            [
                'valor' => 'NE',
                'nombre' => 'No evaluado',
                'descripcion' => 'Destreza o aprendizaje que aún no ha sido abordado ni evaluado en el periodo académico.',
            ],
        ];
    }
}
