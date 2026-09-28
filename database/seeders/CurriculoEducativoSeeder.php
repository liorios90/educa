<?php

namespace Database\Seeders;

use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Subnivel;
use Illuminate\Database\Seeder;

class CurriculoEducativoSeeder extends Seeder
{
    /**
     * Áreas y asignaturas del Currículo Nacional (MINEDUC) por subnivel.
     */
    public function run(): void
    {
        $inicial = $this->ambitosInicial();

        foreach (['Inicial 1', 'Inicial 2'] as $subnivel) {
            $this->seedSubnivel($subnivel, $inicial);
        }

        $this->seedSubnivel('Preparatoria', $this->mallaPreparatoria());
        $this->seedSubnivel('Básica Elemental', $this->mallaElemental());
        $this->seedSubnivel('Básica Media', $this->mallaMedia());
        $this->seedSubnivel('Básica Superior', $this->mallaSuperior());
        $this->seedSubnivel('Bachillerato General Unificado', $this->mallaBachillerato());
    }

    /**
     * @param  list<array{nombre: string, descripcion?: string, aparece_en_libreta?: bool, asignaturas: list<array{nombre: string, horas_semanales?: int|null, aparece_en_libreta?: bool, descripcion?: string}>}>  $areas
     */
    private function seedSubnivel(string $nombre, array $areas): void
    {
        $subnivel = Sys_Subnivel::query()->where('nombre', $nombre)->first();

        if ($subnivel === null) {
            return;
        }

        foreach ($areas as $orden => $areaData) {
            $asignaturas = $areaData['asignaturas'];
            unset($areaData['asignaturas']);

            $area = Sys_Area::query()->updateOrCreate(
                [
                    'subnivel_id' => $subnivel->id,
                    'nombre' => $areaData['nombre'],
                ],
                [
                    'descripcion' => $areaData['descripcion'] ?? null,
                    'orden' => $orden + 1,
                    'aparece_en_libreta' => $areaData['aparece_en_libreta'] ?? true,
                ],
            );

            foreach ($asignaturas as $asignaturaOrden => $asignaturaData) {
                Sys_Asignatura::query()->updateOrCreate(
                    [
                        'area_id' => $area->id,
                        'nombre' => $asignaturaData['nombre'],
                    ],
                    [
                        'descripcion' => $asignaturaData['descripcion'] ?? null,
                        'orden' => $asignaturaOrden + 1,
                        'horas_semanales' => $asignaturaData['horas_semanales'] ?? null,
                        'aparece_en_libreta' => $asignaturaData['aparece_en_libreta'] ?? true,
                    ],
                );
            }
        }
    }

    /**
     * @return list<array{nombre: string, descripcion: string, asignaturas: list<array{nombre: string, descripcion: string}>}>
     */
    private function ambitosInicial(): array
    {
        return [
            [
                'nombre' => 'Desarrollo personal y social',
                'descripcion' => 'Eje de Educación Inicial. Currículo de Educación Inicial MINEDUC.',
                'asignaturas' => [
                    ['nombre' => 'Identidad y autonomía', 'descripcion' => 'Ámbito de desarrollo y aprendizaje.'],
                    ['nombre' => 'Convivencia', 'descripcion' => 'Ámbito de desarrollo y aprendizaje.'],
                ],
            ],
            [
                'nombre' => 'Descubrimiento del medio natural y cultural',
                'descripcion' => 'Eje de Educación Inicial. Currículo de Educación Inicial MINEDUC.',
                'asignaturas' => [
                    ['nombre' => 'Relaciones con el medio natural y cultural', 'descripcion' => 'Ámbito de desarrollo y aprendizaje.'],
                ],
            ],
            [
                'nombre' => 'Expresión y comunicación',
                'descripcion' => 'Eje de Educación Inicial. Currículo de Educación Inicial MINEDUC.',
                'asignaturas' => [
                    ['nombre' => 'Comprensión y expresión del lenguaje', 'descripcion' => 'Ámbito de desarrollo y aprendizaje.'],
                    ['nombre' => 'Expresión artística', 'descripcion' => 'Ámbito de desarrollo y aprendizaje.'],
                    ['nombre' => 'Expresión corporal y motricidad', 'descripcion' => 'Ámbito de desarrollo y aprendizaje.'],
                ],
            ],
        ];
    }

    /**
     * @return list<array{nombre: string, asignaturas: list<array{nombre: string, horas_semanales: int}>}>
     */
    private function mallaPreparatoria(): array
    {
        return $this->areasEgb([
            'Lengua y Literatura' => 10,
            'Matemática' => 8,
            'Ciencias Naturales' => 3,
            'Estudios Sociales' => 3,
            'Educación Cultural y Artística' => 2,
            'Educación Física' => 5,
        ], conIngles: false, conCiudadania: false);
    }

    /**
     * @return list<array{nombre: string, asignaturas: list<array{nombre: string, horas_semanales: int}>}>
     */
    private function mallaElemental(): array
    {
        return $this->areasEgb([
            'Lengua y Literatura' => 8,
            'Matemática' => 7,
            'Ciencias Naturales' => 3,
            'Estudios Sociales' => 3,
            'Educación Cultural y Artística' => 2,
            'Educación Física' => 5,
            'Inglés' => 3,
        ]);
    }

    /**
     * @return list<array{nombre: string, asignaturas: list<array{nombre: string, horas_semanales: int}>}>
     */
    private function mallaMedia(): array
    {
        return $this->areasEgb([
            'Lengua y Literatura' => 6,
            'Matemática' => 6,
            'Ciencias Naturales' => 4,
            'Estudios Sociales' => 4,
            'Educación Cultural y Artística' => 2,
            'Educación Física' => 5,
            'Inglés' => 3,
        ]);
    }

    /**
     * @return list<array{nombre: string, asignaturas: list<array{nombre: string, horas_semanales: int}>}>
     */
    private function mallaSuperior(): array
    {
        return $this->areasEgb([
            'Lengua y Literatura' => 5,
            'Matemática' => 5,
            'Ciencias Naturales' => 4,
            'Estudios Sociales' => 3,
            'Educación para la Ciudadanía' => 2,
            'Educación Cultural y Artística' => 2,
            'Educación Física' => 5,
            'Inglés' => 5,
        ], conIngles: true, conCiudadania: true);
    }

    /**
     * @return list<array{nombre: string, asignaturas: list<array{nombre: string, horas_semanales: int}>}>
     */
    private function mallaBachillerato(): array
    {
        return [
            $this->area('Lengua y Literatura', [
                ['nombre' => 'Lengua y Literatura', 'horas_semanales' => 5],
            ]),
            $this->area('Matemática', [
                ['nombre' => 'Matemática', 'horas_semanales' => 4],
            ]),
            $this->area('Ciencias Naturales', [
                ['nombre' => 'Física', 'horas_semanales' => 4],
                ['nombre' => 'Química', 'horas_semanales' => 3],
                ['nombre' => 'Biología', 'horas_semanales' => 3],
            ]),
            $this->area('Ciencias Sociales', [
                ['nombre' => 'Historia', 'horas_semanales' => 3],
                ['nombre' => 'Educación para la Ciudadanía', 'horas_semanales' => 2],
                ['nombre' => 'Filosofía', 'horas_semanales' => 2],
            ]),
            $this->area('Educación Cultural y Artística', [
                ['nombre' => 'Educación Cultural y Artística', 'horas_semanales' => 2],
            ]),
            $this->area('Educación Física', [
                ['nombre' => 'Educación Física', 'horas_semanales' => 5],
            ]),
            $this->area('Lengua Extranjera', [
                ['nombre' => 'Inglés', 'horas_semanales' => 5],
            ]),
            $this->area('Emprendimiento y Gestión', [
                ['nombre' => 'Emprendimiento y Gestión', 'horas_semanales' => 2],
            ]),
        ];
    }

    /**
     * @param  array<string, int>  $horas
     * @return list<array{nombre: string, asignaturas: list<array{nombre: string, horas_semanales: int}>}>
     */
    private function areasEgb(array $horas, bool $conIngles = true, bool $conCiudadania = false): array
    {
        $areas = [
            $this->area('Lengua y Literatura', [
                ['nombre' => 'Lengua y Literatura', 'horas_semanales' => $horas['Lengua y Literatura']],
            ]),
            $this->area('Matemática', [
                ['nombre' => 'Matemática', 'horas_semanales' => $horas['Matemática']],
            ]),
            $this->area('Ciencias Naturales', [
                ['nombre' => 'Ciencias Naturales', 'horas_semanales' => $horas['Ciencias Naturales']],
            ]),
        ];

        $sociales = [
            ['nombre' => 'Estudios Sociales', 'horas_semanales' => $horas['Estudios Sociales']],
        ];

        if ($conCiudadania) {
            $sociales[] = ['nombre' => 'Educación para la Ciudadanía', 'horas_semanales' => $horas['Educación para la Ciudadanía']];
        }

        $areas[] = $this->area('Ciencias Sociales', $sociales);
        $areas[] = $this->area('Educación Cultural y Artística', [
            ['nombre' => 'Educación Cultural y Artística', 'horas_semanales' => $horas['Educación Cultural y Artística']],
        ]);
        $areas[] = $this->area('Educación Física', [
            ['nombre' => 'Educación Física', 'horas_semanales' => $horas['Educación Física']],
        ]);

        if ($conIngles) {
            $areas[] = $this->area('Lengua Extranjera', [
                ['nombre' => 'Inglés', 'horas_semanales' => $horas['Inglés']],
            ]);
        }

        return $areas;
    }

    /**
     * @param  list<array{nombre: string, horas_semanales: int}>  $asignaturas
     * @return array{nombre: string, descripcion: string, asignaturas: list<array{nombre: string, horas_semanales: int}>}
     */
    private function area(string $nombre, array $asignaturas): array
    {
        return [
            'nombre' => $nombre,
            'descripcion' => 'Área del Currículo Nacional de Educación Obligatoria.',
            'asignaturas' => $asignaturas,
        ];
    }
}
