<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Alumno;
use App\Models\Establecimiento;
use App\Models\Padre;
use App\Models\Persona;
use App\Models\Sys_Provincia;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role as SpatieRole;

class DemoFamiliasSisEducaSeeder extends Seeder
{
    private const CODIGO_AMIE = '01AS01061';

    /**
     * Nueve representantes y diez estudiantes de la Unidad Educativa Sis Educa.
     * Mateo y Sofía Villacís son hermanos y comparten representante.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        DB::transaction(function (): void {
            $establecimiento = Establecimiento::query()
                ->where('codigo_amie', self::CODIGO_AMIE)
                ->firstOrFail();
            $provincia = Sys_Provincia::query()->where('nombre', 'Azuay')->first();

            if ($provincia === null) {
                throw new RuntimeException('Falta la provincia Azuay para las familias de demostración.');
            }

            $rolPadre = SpatieRole::findByName(Role::Padre->value, 'web');
            $rolAlumno = SpatieRole::findByName(Role::Alumno->value, 'web');

            foreach ($this->familias() as $familia) {
                $padre = $this->padre($familia['padre'], $establecimiento, $provincia, $rolPadre);

                foreach ($familia['estudiantes'] as $estudiante) {
                    $this->estudiante($estudiante, $familia['padre'], $padre, $establecimiento, $provincia, $rolAlumno);
                }
            }
        });
    }

    /**
     * @param  array{identificacion: string, nombres: string, apellidos: string, genero_id: int, fecha_nacimiento: string, estado_civil: string, titulo: string, parroquia: string, direccion: string, telefono1: string, telefono2: string, email: string}  $datos
     */
    private function padre(array $datos, Establecimiento $establecimiento, Sys_Provincia $provincia, SpatieRole $rol): Padre
    {
        $persona = $this->persona($datos, $establecimiento, $provincia, $rol, $datos['fecha_nacimiento']);

        return Padre::query()->updateOrCreate(
            ['persona_id' => $persona->id],
            [
                'estado_civil_id' => $datos['estado_civil'],
                'vive_con_estudiante' => 1,
                'titulo' => $datos['titulo'],
                'usuario' => 'demostracion',
                'activo' => 1,
            ],
        );
    }

    /**
     * @param  array{identificacion: string, nombres: string, apellidos: string, genero_id: int, fecha_nacimiento: string, email: string}  $datos
     * @param  array{nombres: string, apellidos: string, parroquia: string, direccion: string, telefono1: string, telefono2: string}  $hogar
     */
    private function estudiante(
        array $datos,
        array $hogar,
        Padre $padre,
        Establecimiento $establecimiento,
        Sys_Provincia $provincia,
        SpatieRole $rol,
    ): Alumno {
        $persona = $this->persona(
            [
                ...$datos,
                'parroquia' => $hogar['parroquia'],
                'direccion' => $hogar['direccion'],
                'telefono1' => $hogar['telefono1'],
                'telefono2' => $hogar['telefono2'],
            ],
            $establecimiento,
            $provincia,
            $rol,
            $datos['fecha_nacimiento'],
        );

        return Alumno::query()->updateOrCreate(
            ['persona_id' => $persona->id],
            [
                'padre_id' => $padre->id,
                'contacto_emergencia' => trim($hogar['nombres'].' '.$hogar['apellidos'].' '.$hogar['telefono2']),
                'usuario' => 'demostracion',
                'id_estructura_form_matricula' => 0,
                'activo' => 1,
            ],
        );
    }

    /**
     * @param  array{identificacion: string, nombres: string, apellidos: string, genero_id: int, email: string, parroquia: string, direccion: string, telefono1: string, telefono2: string}  $datos
     */
    private function persona(
        array $datos,
        Establecimiento $establecimiento,
        Sys_Provincia $provincia,
        SpatieRole $rol,
        string $fechaNacimiento,
    ): Persona {
        $user = User::query()->updateOrCreate(
            ['email' => $datos['email']],
            [
                'name' => trim($datos['nombres'].' '.$datos['apellidos']),
                'password' => 'password',
                'establecimiento_id' => $establecimiento->id,
            ],
        );
        $user->syncRoles([$rol]);

        return Persona::query()->updateOrCreate(
            ['identificacion' => $datos['identificacion']],
            [
                'user_id' => $user->id,
                'tipo_identificacion_id' => 1,
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'genero_id' => $datos['genero_id'],
                'fecha_nacimiento' => $fechaNacimiento,
                'ciudad_nacimiento' => 'Cuenca',
                'provincia_id' => $provincia->id,
                'parroquia' => $datos['parroquia'],
                'direccion' => $datos['direccion'],
                'telefono1' => $datos['telefono1'],
                'telefono2' => $datos['telefono2'],
                'nacionalidad_id' => $provincia->pais_id,
                'usuario' => 'demostracion',
                'activo' => 1,
                'establecimiento_id' => $establecimiento->id,
            ],
        );
    }

    /**
     * @return list<array{
     *     padre: array{identificacion: string, nombres: string, apellidos: string, genero_id: int, fecha_nacimiento: string, estado_civil: string, titulo: string, parroquia: string, direccion: string, telefono1: string, telefono2: string, email: string},
     *     estudiantes: list<array{identificacion: string, nombres: string, apellidos: string, genero_id: int, fecha_nacimiento: string, email: string}>
     * }>
     */
    private function familias(): array
    {
        return [
            [
                'padre' => [
                    'identificacion' => '0102347816',
                    'nombres' => 'María Elena',
                    'apellidos' => 'Villacís Cárdenas',
                    'genero_id' => 2,
                    'fecha_nacimiento' => '1982-04-16',
                    'estado_civil' => 'Casado',
                    'titulo' => 'Ingeniera comercial',
                    'parroquia' => 'El Batán',
                    'direccion' => 'Calle Los Cipreses 2-14 y De los Fresnos',
                    'telefono1' => '072814563',
                    'telefono2' => '0994123876',
                    'email' => 'maria.villacis@siseduca.test',
                ],
                'estudiantes' => [
                    [
                        'identificacion' => '0105672104',
                        'nombres' => 'Mateo Alejandro',
                        'apellidos' => 'Villacís Cárdenas',
                        'genero_id' => 1,
                        'fecha_nacimiento' => '2012-09-03',
                        'email' => 'mateo.villacis@siseduca.test',
                    ],
                    [
                        'identificacion' => '0105678841',
                        'nombres' => 'Sofía Valentina',
                        'apellidos' => 'Villacís Cárdenas',
                        'genero_id' => 2,
                        'fecha_nacimiento' => '2014-02-21',
                        'email' => 'sofia.villacis@siseduca.test',
                    ],
                ],
            ],
            [
                'padre' => [
                    'identificacion' => '0103459028',
                    'nombres' => 'Carlos Andrés',
                    'apellidos' => 'Quishpe Maldonado',
                    'genero_id' => 1,
                    'fecha_nacimiento' => '1979-11-08',
                    'estado_civil' => 'Casado',
                    'titulo' => 'Contador',
                    'parroquia' => 'San Blas',
                    'direccion' => 'Calle Bolívar 8-21 y Presidente Córdova',
                    'telefono1' => '072823041',
                    'telefono2' => '0987652140',
                    'email' => 'carlos.quishpe@siseduca.test',
                ],
                'estudiantes' => [
                    [
                        'identificacion' => '0106123308',
                        'nombres' => 'Santiago Nicolás',
                        'apellidos' => 'Quishpe Maldonado',
                        'genero_id' => 1,
                        'fecha_nacimiento' => '2011-06-14',
                        'email' => 'santiago.quishpe@siseduca.test',
                    ],
                ],
            ],
            [
                'padre' => [
                    'identificacion' => '0102876543',
                    'nombres' => 'Patricia Soledad',
                    'apellidos' => 'Caiza Andrade',
                    'genero_id' => 2,
                    'fecha_nacimiento' => '1986-07-29',
                    'estado_civil' => 'Unión libre',
                    'titulo' => 'Docente',
                    'parroquia' => 'Huayna Cápac',
                    'direccion' => 'Av. Solano 3-78 y Florencia Astudillo',
                    'telefono1' => '072841902',
                    'telefono2' => '0998734512',
                    'email' => 'patricia.caiza@siseduca.test',
                ],
                'estudiantes' => [
                    [
                        'identificacion' => '0106347782',
                        'nombres' => 'Isabella Camila',
                        'apellidos' => 'Caiza Andrade',
                        'genero_id' => 2,
                        'fecha_nacimiento' => '2013-01-19',
                        'email' => 'isabella.caiza@siseduca.test',
                    ],
                ],
            ],
            [
                'padre' => [
                    'identificacion' => '0101987432',
                    'nombres' => 'Jorge Luis',
                    'apellidos' => 'Cevallos Paredes',
                    'genero_id' => 1,
                    'fecha_nacimiento' => '1976-03-02',
                    'estado_civil' => 'Casado',
                    'titulo' => 'Arquitecto',
                    'parroquia' => 'El Sagrario',
                    'direccion' => 'Calle Larga 5-12 y Hermano Miguel',
                    'telefono1' => '072820114',
                    'telefono2' => '0983216704',
                    'email' => 'jorge.cevallos@siseduca.test',
                ],
                'estudiantes' => [
                    [
                        'identificacion' => '0105893347',
                        'nombres' => 'Sebastián David',
                        'apellidos' => 'Cevallos Paredes',
                        'genero_id' => 1,
                        'fecha_nacimiento' => '2010-08-27',
                        'email' => 'sebastian.cevallos@siseduca.test',
                    ],
                ],
            ],
            [
                'padre' => [
                    'identificacion' => '0104562187',
                    'nombres' => 'Ana Lucía',
                    'apellidos' => 'Zambrano Navarrete',
                    'genero_id' => 2,
                    'fecha_nacimiento' => '1988-12-11',
                    'estado_civil' => 'Divorciado',
                    'titulo' => 'Abogada',
                    'parroquia' => 'San Sebastián',
                    'direccion' => 'Calle Gran Colombia 11-30 y Benigno Malo',
                    'telefono1' => '072836775',
                    'telefono2' => '0992145683',
                    'email' => 'ana.zambrano@siseduca.test',
                ],
                'estudiantes' => [
                    [
                        'identificacion' => '0106459021',
                        'nombres' => 'Emilia Renata',
                        'apellidos' => 'Zambrano Navarrete',
                        'genero_id' => 2,
                        'fecha_nacimiento' => '2015-05-09',
                        'email' => 'emilia.zambrano@siseduca.test',
                    ],
                ],
            ],
            [
                'padre' => [
                    'identificacion' => '0103765409',
                    'nombres' => 'Fernando José',
                    'apellidos' => 'Chávez Molina',
                    'genero_id' => 1,
                    'fecha_nacimiento' => '1984-01-25',
                    'estado_civil' => 'Casado',
                    'titulo' => 'Técnico electricista',
                    'parroquia' => 'Cañaribamba',
                    'direccion' => 'Av. De las Américas y Unidad Nacional',
                    'telefono1' => '072809334',
                    'telefono2' => '0986541290',
                    'email' => 'fernando.chavez@siseduca.test',
                ],
                'estudiantes' => [
                    [
                        'identificacion' => '0106231194',
                        'nombres' => 'Joaquín Andrés',
                        'apellidos' => 'Chávez Molina',
                        'genero_id' => 1,
                        'fecha_nacimiento' => '2012-11-30',
                        'email' => 'joaquin.chavez@siseduca.test',
                    ],
                ],
            ],
            [
                'padre' => [
                    'identificacion' => '0105126674',
                    'nombres' => 'Rosa Elena',
                    'apellidos' => 'Salazar Espinoza',
                    'genero_id' => 2,
                    'fecha_nacimiento' => '1981-09-07',
                    'estado_civil' => 'Casado',
                    'titulo' => 'Enfermera',
                    'parroquia' => 'Totoracocha',
                    'direccion' => 'Barrio Totoracocha, calle Paseo de los Cañaris N4-18',
                    'telefono1' => '072851226',
                    'telefono2' => '0995678341',
                    'email' => 'rosa.salazar@siseduca.test',
                ],
                'estudiantes' => [
                    [
                        'identificacion' => '0106782456',
                        'nombres' => 'Lucía Antonella',
                        'apellidos' => 'Salazar Espinoza',
                        'genero_id' => 2,
                        'fecha_nacimiento' => '2016-03-12',
                        'email' => 'lucia.salazar@siseduca.test',
                    ],
                ],
            ],
            [
                'padre' => [
                    'identificacion' => '0102678901',
                    'nombres' => 'Diego Alejandro',
                    'apellidos' => 'Rivadeneira García',
                    'genero_id' => 1,
                    'fecha_nacimiento' => '1983-06-18',
                    'estado_civil' => 'Unión libre',
                    'titulo' => 'Comerciante',
                    'parroquia' => 'Gil Ramírez Dávalos',
                    'direccion' => 'Cdla. El Paraíso, pasaje Los Geranios 6-09',
                    'telefono1' => '072844018',
                    'telefono2' => '0989023176',
                    'email' => 'diego.rivadeneira@siseduca.test',
                ],
                'estudiantes' => [
                    [
                        'identificacion' => '0106017783',
                        'nombres' => 'Gabriel Esteban',
                        'apellidos' => 'Rivadeneira García',
                        'genero_id' => 1,
                        'fecha_nacimiento' => '2011-12-05',
                        'email' => 'gabriel.rivadeneira@siseduca.test',
                    ],
                ],
            ],
            [
                'padre' => [
                    'identificacion' => '0104893320',
                    'nombres' => 'Carmen Beatriz',
                    'apellidos' => 'López González',
                    'genero_id' => 2,
                    'fecha_nacimiento' => '1990-02-14',
                    'estado_civil' => 'Soltero',
                    'titulo' => 'Administradora',
                    'parroquia' => 'Machángara',
                    'direccion' => 'Barrio Machángara, calle Tarqui 9-45 y Vega Muñoz',
                    'telefono1' => '072818902',
                    'telefono2' => '0993412087',
                    'email' => 'carmen.lopez@siseduca.test',
                ],
                'estudiantes' => [
                    [
                        'identificacion' => '0106901452',
                        'nombres' => 'Martina Paula',
                        'apellidos' => 'López González',
                        'genero_id' => 2,
                        'fecha_nacimiento' => '2015-10-22',
                        'email' => 'martina.lopez@siseduca.test',
                    ],
                ],
            ],
        ];
    }
}
