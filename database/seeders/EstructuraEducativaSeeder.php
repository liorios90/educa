<?php

namespace Database\Seeders;

use App\Enums\TipoCalificacion;
use App\Models\Sys_Grado;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use Illuminate\Database\Seeder;

class EstructuraEducativaSeeder extends Seeder
{
    /**
     * Niveles, subniveles y grados de la educación escolarizada ecuatoriana
     * (LOEI arts. 39-43 y Reglamento General a la LOEI).
     */
    public function run(): void
    {
        Sys_Grado::query()->delete();
        Sys_Subnivel::query()->delete();
        Sys_Nivel::query()->delete();

        foreach ($this->estructura() as $nivelData) {
            $subniveles = $nivelData['subniveles'];
            unset($nivelData['subniveles']);

            $nivel = Sys_Nivel::query()->updateOrCreate(
                ['nombre' => $nivelData['nombre']],
                [
                    'siglas' => $nivelData['siglas'],
                    'descripcion' => $nivelData['descripcion'],
                ],
            );

            foreach ($subniveles as $subnivelData) {
                $grados = $subnivelData['grados'];
                unset($subnivelData['grados']);

                $subnivel = Sys_Subnivel::query()->updateOrCreate(
                    ['nombre' => $subnivelData['nombre']],
                    [
                        'siglas' => $subnivelData['siglas'],
                        'descripcion' => $subnivelData['descripcion'],
                        'tipo_calificacion' => $subnivelData['tipo_calificacion'],
                        'nivel_id' => $nivel->id,
                    ],
                );

                foreach ($grados as $gradoData) {
                    Sys_Grado::query()->updateOrCreate(
                        ['nombre' => $gradoData['nombre']],
                        [
                            'siglas' => $gradoData['siglas'],
                            'descripcion' => $gradoData['descripcion'],
                            'subnivel_id' => $subnivel->id,
                        ],
                    );
                }
            }
        }
    }

    /**
     * @return list<array{
     *     nombre: string,
     *     siglas: string,
     *     descripcion: string,
     *     subniveles: list<array{
     *         nombre: string,
     *         siglas: string,
     *         descripcion: string,
     *         tipo_calificacion: TipoCalificacion,
     *         grados: list<array{nombre: string, siglas: string, descripcion: string}>
     *     }>
     * }>
     */
    private function estructura(): array
    {
        return [
            [
                'nombre' => 'Educación Inicial',
                'siglas' => 'EI',
                'descripcion' => 'Acompañamiento al desarrollo integral de infantes, según los arts. 39 y 40 de la LOEI.',
                'subniveles' => [
                    [
                        'nombre' => 'Inicial 1',
                        'siglas' => 'INI1',
                        'descripcion' => 'Infantes de hasta 3 años. Subnivel 1 del Reglamento General a la LOEI.',
                        'tipo_calificacion' => TipoCalificacion::Destrezas,
                        'grados' => [
                            [
                                'nombre' => 'Inicial 1',
                                'siglas' => 'INI1',
                                'descripcion' => 'Grupo de hasta 3 años de edad.',
                            ],
                        ],
                    ],
                    [
                        'nombre' => 'Inicial 2',
                        'siglas' => 'INI2',
                        'descripcion' => 'Infantes de 3 a 5 años. Subnivel 2 del Reglamento General a la LOEI.',
                        'tipo_calificacion' => TipoCalificacion::Destrezas,
                        'grados' => [
                            [
                                'nombre' => 'Inicial 2',
                                'siglas' => 'INI2',
                                'descripcion' => 'Grupo de 3 a 5 años de edad.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'nombre' => 'Educación General Básica',
                'siglas' => 'EGB',
                'descripcion' => 'Diez grados de educación obligatoria, según el art. 42 de la LOEI.',
                'subniveles' => [
                    [
                        'nombre' => 'Preparatoria',
                        'siglas' => 'PREP',
                        'descripcion' => '1.er grado de EGB, preferentemente a los 5 años.',
                        'tipo_calificacion' => TipoCalificacion::Destrezas,
                        'grados' => [
                            [
                                'nombre' => 'Primero de EGB',
                                'siglas' => '1EGB',
                                'descripcion' => 'Primer grado de Educación General Básica.',
                            ],
                        ],
                    ],
                    [
                        'nombre' => 'Básica Elemental',
                        'siglas' => 'BE',
                        'descripcion' => '2.º, 3.º y 4.º grados de EGB, preferentemente de 6 a 8 años.',
                        'tipo_calificacion' => TipoCalificacion::Calificacion,
                        'grados' => [
                            [
                                'nombre' => 'Segundo de EGB',
                                'siglas' => '2EGB',
                                'descripcion' => 'Segundo grado de Educación General Básica.',
                            ],
                            [
                                'nombre' => 'Tercero de EGB',
                                'siglas' => '3EGB',
                                'descripcion' => 'Tercer grado de Educación General Básica.',
                            ],
                            [
                                'nombre' => 'Cuarto de EGB',
                                'siglas' => '4EGB',
                                'descripcion' => 'Cuarto grado de Educación General Básica.',
                            ],
                        ],
                    ],
                    [
                        'nombre' => 'Básica Media',
                        'siglas' => 'BM',
                        'descripcion' => '5.º, 6.º y 7.º grados de EGB, preferentemente de 9 a 11 años.',
                        'tipo_calificacion' => TipoCalificacion::Calificacion,
                        'grados' => [
                            [
                                'nombre' => 'Quinto de EGB',
                                'siglas' => '5EGB',
                                'descripcion' => 'Quinto grado de Educación General Básica.',
                            ],
                            [
                                'nombre' => 'Sexto de EGB',
                                'siglas' => '6EGB',
                                'descripcion' => 'Sexto grado de Educación General Básica.',
                            ],
                            [
                                'nombre' => 'Séptimo de EGB',
                                'siglas' => '7EGB',
                                'descripcion' => 'Séptimo grado de Educación General Básica.',
                            ],
                        ],
                    ],
                    [
                        'nombre' => 'Básica Superior',
                        'siglas' => 'BS',
                        'descripcion' => '8.º, 9.º y 10.º grados de EGB, preferentemente de 12 a 14 años.',
                        'tipo_calificacion' => TipoCalificacion::Calificacion,
                        'grados' => [
                            [
                                'nombre' => 'Octavo de EGB',
                                'siglas' => '8EGB',
                                'descripcion' => 'Octavo grado de Educación General Básica.',
                            ],
                            [
                                'nombre' => 'Noveno de EGB',
                                'siglas' => '9EGB',
                                'descripcion' => 'Noveno grado de Educación General Básica.',
                            ],
                            [
                                'nombre' => 'Décimo de EGB',
                                'siglas' => '10EGB',
                                'descripcion' => 'Décimo grado de Educación General Básica.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'nombre' => 'Bachillerato',
                'siglas' => 'BGU',
                'descripcion' => 'Tres cursos obligatorios posteriores a la EGB, según el art. 43 de la LOEI.',
                'subniveles' => [
                    [
                        'nombre' => 'Bachillerato General Unificado',
                        'siglas' => 'BGU',
                        'descripcion' => '1.er, 2.º y 3.er cursos, preferentemente de 15 a 17 años.',
                        'tipo_calificacion' => TipoCalificacion::Calificacion,
                        'grados' => [
                            [
                                'nombre' => 'Primero de Bachillerato',
                                'siglas' => '1BGU',
                                'descripcion' => 'Primer curso de Bachillerato General Unificado.',
                            ],
                            [
                                'nombre' => 'Segundo de Bachillerato',
                                'siglas' => '2BGU',
                                'descripcion' => 'Segundo curso de Bachillerato General Unificado.',
                            ],
                            [
                                'nombre' => 'Tercero de Bachillerato',
                                'siglas' => '3BGU',
                                'descripcion' => 'Tercer curso de Bachillerato General Unificado.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
