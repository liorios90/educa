<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\Persona;
use App\Models\Sys_Funcion;
use App\Models\Sys_Provincia;
use App\Models\Sys_TipoContrato;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role as SpatieRole;

class DemoEmpleadosSisEducaSeeder extends Seeder
{
    private const CODIGO_AMIE = '01AS01061';

    /**
     * Una secretaria y catorce docentes de la Unidad Educativa Sis Educa.
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
                throw new RuntimeException('Falta la provincia Azuay para los empleados de demostración.');
            }

            $contratos = $this->contratos();
            $funciones = $this->funciones();
            $roles = [
                Role::Secretaria->value => SpatieRole::findByName(Role::Secretaria->value, 'web'),
                Role::Docente->value => SpatieRole::findByName(Role::Docente->value, 'web'),
            ];

            foreach ($this->empleados() as $datos) {
                $this->empleado($datos, $establecimiento, $provincia, $contratos, $funciones, $roles);
            }
        });
    }

    /**
     * @param  array{identificacion: string, nombres: string, apellidos: string, genero_id: int, fecha_nacimiento: string, parroquia: string, direccion: string, telefono1: string, telefono2: string, email: string, contrato: string, funcion: string, rol: string, horas: int, anios_experiencia: int, anios_instituto: int, contacto_emergencia: string, contacto_num: string}  $datos
     * @param  array<string, Sys_TipoContrato>  $contratos
     * @param  array<string, Sys_Funcion>  $funciones
     * @param  array<string, SpatieRole>  $roles
     */
    private function empleado(
        array $datos,
        Establecimiento $establecimiento,
        Sys_Provincia $provincia,
        array $contratos,
        array $funciones,
        array $roles,
    ): Empleado {
        $user = User::query()->updateOrCreate(
            ['email' => $datos['email']],
            [
                'name' => trim($datos['nombres'].' '.$datos['apellidos']),
                'password' => 'password',
                'establecimiento_id' => $establecimiento->id,
            ],
        );
        $user->syncRoles([$roles[$datos['rol']]]);

        $persona = Persona::query()->updateOrCreate(
            ['identificacion' => $datos['identificacion']],
            [
                'user_id' => $user->id,
                'tipo_identificacion_id' => 1,
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'genero_id' => $datos['genero_id'],
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
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

        return Empleado::query()->updateOrCreate(
            ['persona_id' => $persona->id],
            [
                'tipo_contrato_id' => $contratos[$datos['contrato']]->id,
                'cargo_id' => null,
                'funcion_id' => $funciones[$datos['funcion']]->id,
                'horas' => $datos['horas'],
                'anios_experiencia' => $datos['anios_experiencia'],
                'anios_instituto' => $datos['anios_instituto'],
                'contacto_emergencia' => $datos['contacto_emergencia'],
                'contacto_num' => $datos['contacto_num'],
                'usuario' => 'demostracion',
                'activo' => 1,
            ],
        );
    }

    /**
     * @return array<string, Sys_TipoContrato>
     */
    private function contratos(): array
    {
        return [
            'nombramiento' => Sys_TipoContrato::query()->firstOrCreate(
                ['codigo' => 'NOM'],
                [
                    'nombre' => 'Nombramiento',
                    'descripcion' => 'Nombramiento en el magisterio fiscal.',
                    'usuario' => 'demostracion',
                    'activo' => 1,
                ],
            ),
            'ocasional' => Sys_TipoContrato::query()->firstOrCreate(
                ['codigo' => 'OCA'],
                [
                    'nombre' => 'Contrato ocasional',
                    'descripcion' => 'Contrato ocasional de servicios profesionales.',
                    'usuario' => 'demostracion',
                    'activo' => 1,
                ],
            ),
        ];
    }

    /**
     * @return array<string, Sys_Funcion>
     */
    private function funciones(): array
    {
        return [
            'Secretaría' => Sys_Funcion::query()->firstOrCreate(
                ['nombre' => 'Secretaría'],
                [
                    'descripcion' => 'Apoyo administrativo de secretaría del plantel.',
                    'usuario' => 'demostracion',
                    'activo' => 1,
                ],
            ),
            'Docente' => Sys_Funcion::query()->firstOrCreate(
                ['nombre' => 'Docente'],
                [
                    'descripcion' => 'Docente de aula.',
                    'usuario' => 'demostracion',
                    'activo' => 1,
                ],
            ),
        ];
    }

    /**
     * @return list<array{identificacion: string, nombres: string, apellidos: string, genero_id: int, fecha_nacimiento: string, parroquia: string, direccion: string, telefono1: string, telefono2: string, email: string, contrato: string, funcion: string, rol: string, horas: int, anios_experiencia: int, anios_instituto: int, contacto_emergencia: string, contacto_num: string}>
     */
    private function empleados(): array
    {
        return [
            [
                'identificacion' => '0107234518',
                'nombres' => 'Verónica Patricia',
                'apellidos' => 'Orellana Bermeo',
                'genero_id' => 2,
                'fecha_nacimiento' => '1984-05-12',
                'parroquia' => 'El Vergel',
                'direccion' => 'Av. Solano 4-28 y Remigio Crespo',
                'telefono1' => '072845110',
                'telefono2' => '0993184520',
                'email' => 'veronica.orellana@siseduca.test',
                'contrato' => 'nombramiento',
                'funcion' => 'Secretaría',
                'rol' => Role::Secretaria->value,
                'horas' => 40,
                'anios_experiencia' => 16,
                'anios_instituto' => 9,
                'contacto_emergencia' => 'Luis Orellana',
                'contacto_num' => '0987123406',
            ],
            [
                'identificacion' => '0108129034',
                'nombres' => 'Luis Fernando',
                'apellidos' => 'Aucapiña Jara',
                'genero_id' => 1,
                'fecha_nacimiento' => '1978-08-03',
                'parroquia' => 'San Blas',
                'direccion' => 'Calle Bolívar 6-15 y Benigno Malo',
                'telefono1' => '072821904',
                'telefono2' => '0986547712',
                'email' => 'luis.aucapina@siseduca.test',
                'contrato' => 'nombramiento',
                'funcion' => 'Docente',
                'rol' => Role::Docente->value,
                'horas' => 30,
                'anios_experiencia' => 22,
                'anios_instituto' => 14,
                'contacto_emergencia' => 'Martha Jara',
                'contacto_num' => '0992041187',
            ],
            [
                'identificacion' => '0109345602',
                'nombres' => 'Mónica Isabel',
                'apellidos' => 'Peralta Cueva',
                'genero_id' => 2,
                'fecha_nacimiento' => '1986-01-27',
                'parroquia' => 'Huayna Cápac',
                'direccion' => 'Av. Huayna Cápac 2-90 y Paseo de los Cañaris',
                'telefono1' => '072836441',
                'telefono2' => '0998732204',
                'email' => 'monica.peralta@siseduca.test',
                'contrato' => 'nombramiento',
                'funcion' => 'Docente',
                'rol' => Role::Docente->value,
                'horas' => 30,
                'anios_experiencia' => 15,
                'anios_instituto' => 8,
                'contacto_emergencia' => 'Pedro Peralta',
                'contacto_num' => '0983419075',
            ],
            [
                'identificacion' => '0106789015',
                'nombres' => 'Pablo Esteban',
                'apellidos' => 'Guamán Tapia',
                'genero_id' => 1,
                'fecha_nacimiento' => '1981-11-19',
                'parroquia' => 'Totoracocha',
                'direccion' => 'Barrio Totoracocha, calle de los Cerezos 8-12',
                'telefono1' => '072851773',
                'telefono2' => '0991265480',
                'email' => 'pablo.guaman@siseduca.test',
                'contrato' => 'nombramiento',
                'funcion' => 'Docente',
                'rol' => Role::Docente->value,
                'horas' => 30,
                'anios_experiencia' => 18,
                'anios_instituto' => 11,
                'contacto_emergencia' => 'Rosa Tapia',
                'contacto_num' => '0989072314',
            ],
            [
                'identificacion' => '0107452186',
                'nombres' => 'Adriana Cristina',
                'apellidos' => 'Moscoso Beltrán',
                'genero_id' => 2,
                'fecha_nacimiento' => '1989-04-06',
                'parroquia' => 'El Batán',
                'direccion' => 'Calle Los Claveles 1-33 y De los Álamos',
                'telefono1' => '072814908',
                'telefono2' => '0995608123',
                'email' => 'adriana.moscoso@siseduca.test',
                'contrato' => 'nombramiento',
                'funcion' => 'Docente',
                'rol' => Role::Docente->value,
                'horas' => 30,
                'anios_experiencia' => 12,
                'anios_instituto' => 6,
                'contacto_emergencia' => 'Javier Moscoso',
                'contacto_num' => '0982237610',
            ],
            [
                'identificacion' => '0108903347',
                'nombres' => 'Ricardo Daniel',
                'apellidos' => 'Ortiz Vélez',
                'genero_id' => 1,
                'fecha_nacimiento' => '1983-09-14',
                'parroquia' => 'Yanuncay',
                'direccion' => 'Av. Loja 12-40 y Del Tejar',
                'telefono1' => '072809662',
                'telefono2' => '0987765401',
                'email' => 'ricardo.ortiz@siseduca.test',
                'contrato' => 'nombramiento',
                'funcion' => 'Docente',
                'rol' => Role::Docente->value,
                'horas' => 30,
                'anios_experiencia' => 17,
                'anios_instituto' => 10,
                'contacto_emergencia' => 'Carmen Vélez',
                'contacto_num' => '0994182036',
            ],
            [
                'identificacion' => '0109567128',
                'nombres' => 'Gabriela Noemí',
                'apellidos' => 'Pesántez León',
                'genero_id' => 2,
                'fecha_nacimiento' => '1991-07-22',
                'parroquia' => 'Bellavista',
                'direccion' => 'Cdla. Bellavista, calle Miguel Moreno 5-18',
                'telefono1' => '072842215',
                'telefono2' => '0992348701',
                'email' => 'gabriela.pesantez@siseduca.test',
                'contrato' => 'nombramiento',
                'funcion' => 'Docente',
                'rol' => Role::Docente->value,
                'horas' => 30,
                'anios_experiencia' => 9,
                'anios_instituto' => 5,
                'contacto_emergencia' => 'Andrés Pesántez',
                'contacto_num' => '0985612094',
            ],
            [
                'identificacion' => '0108344091',
                'nombres' => 'Hugo Mauricio',
                'apellidos' => 'Calle Brito',
                'genero_id' => 1,
                'fecha_nacimiento' => '1975-02-09',
                'parroquia' => 'El Sagrario',
                'direccion' => 'Calle Larga 7-02 y Padre Aguirre',
                'telefono1' => '072820557',
                'telefono2' => '0998013345',
                'email' => 'hugo.calle@siseduca.test',
                'contrato' => 'nombramiento',
                'funcion' => 'Docente',
                'rol' => Role::Docente->value,
                'horas' => 30,
                'anios_experiencia' => 26,
                'anios_instituto' => 18,
                'contacto_emergencia' => 'Beatriz Brito',
                'contacto_num' => '0981145629',
            ],
            [
                'identificacion' => '0107678230',
                'nombres' => 'Silvia Marlene',
                'apellidos' => 'Astudillo Rojas',
                'genero_id' => 2,
                'fecha_nacimiento' => '1980-12-01',
                'parroquia' => 'San Sebastián',
                'direccion' => 'Calle Gran Colombia 9-44 y General Torres',
                'telefono1' => '072833190',
                'telefono2' => '0996721483',
                'email' => 'silvia.astudillo@siseduca.test',
                'contrato' => 'nombramiento',
                'funcion' => 'Docente',
                'rol' => Role::Docente->value,
                'horas' => 30,
                'anios_experiencia' => 20,
                'anios_instituto' => 13,
                'contacto_emergencia' => 'Fernando Astudillo',
                'contacto_num' => '0984309172',
            ],
            [
                'identificacion' => '0109012564',
                'nombres' => 'Andrés Felipe',
                'apellidos' => 'Narváez Dután',
                'genero_id' => 1,
                'fecha_nacimiento' => '1992-03-18',
                'parroquia' => 'Monay',
                'direccion' => 'Av. De las Américas y González Suárez',
                'telefono1' => '072858304',
                'telefono2' => '0991456802',
                'email' => 'andres.narvaez@siseduca.test',
                'contrato' => 'ocasional',
                'funcion' => 'Docente',
                'rol' => Role::Docente->value,
                'horas' => 25,
                'anios_experiencia' => 6,
                'anios_instituto' => 2,
                'contacto_emergencia' => 'Lucía Dután',
                'contacto_num' => '0987762105',
            ],
            [
                'identificacion' => '0108236679',
                'nombres' => 'Cristina Alejandra',
                'apellidos' => 'Barros Méndez',
                'genero_id' => 2,
                'fecha_nacimiento' => '1987-06-30',
                'parroquia' => 'Cañaribamba',
                'direccion' => 'Cdla. El Paraíso, pasaje Las Gardenias 3-07',
                'telefono1' => '072847661',
                'telefono2' => '0993087614',
                'email' => 'cristina.barros@siseduca.test',
                'contrato' => 'nombramiento',
                'funcion' => 'Docente',
                'rol' => Role::Docente->value,
                'horas' => 30,
                'anios_experiencia' => 13,
                'anios_instituto' => 7,
                'contacto_emergencia' => 'Iván Barros',
                'contacto_num' => '0982094451',
            ],
            [
                'identificacion' => '0107561903',
                'nombres' => 'Marcelo Iván',
                'apellidos' => 'Peñafiel Suárez',
                'genero_id' => 1,
                'fecha_nacimiento' => '1979-10-25',
                'parroquia' => 'Gil Ramírez Dávalos',
                'direccion' => 'Calle Tarqui 4-19 y Vega Muñoz',
                'telefono1' => '072818442',
                'telefono2' => '0989432170',
                'email' => 'marcelo.penafiel@siseduca.test',
                'contrato' => 'nombramiento',
                'funcion' => 'Docente',
                'rol' => Role::Docente->value,
                'horas' => 30,
                'anios_experiencia' => 21,
                'anios_instituto' => 12,
                'contacto_emergencia' => 'Patricia Suárez',
                'contacto_num' => '0995812037',
            ],
            [
                'identificacion' => '0106894427',
                'nombres' => 'Elena Margarita',
                'apellidos' => 'Cordero Vintimilla',
                'genero_id' => 2,
                'fecha_nacimiento' => '1985-08-08',
                'parroquia' => 'Sucre',
                'direccion' => 'Calle Sucre 3-55 y Coronel Talbot',
                'telefono1' => '072826903',
                'telefono2' => '0992674318',
                'email' => 'elena.cordero@siseduca.test',
                'contrato' => 'nombramiento',
                'funcion' => 'Docente',
                'rol' => Role::Docente->value,
                'horas' => 30,
                'anios_experiencia' => 16,
                'anios_instituto' => 9,
                'contacto_emergencia' => 'Raúl Cordero',
                'contacto_num' => '0986501284',
            ],
            [
                'identificacion' => '0109120785',
                'nombres' => 'Jaime Rodrigo',
                'apellidos' => 'Ugalde Córdova',
                'genero_id' => 1,
                'fecha_nacimiento' => '1990-01-16',
                'parroquia' => 'Machángara',
                'direccion' => 'Barrio Machángara, calle Paseo Río Machángara N2-21',
                'telefono1' => '072863118',
                'telefono2' => '0997145806',
                'email' => 'jaime.ugalde@siseduca.test',
                'contrato' => 'ocasional',
                'funcion' => 'Docente',
                'rol' => Role::Docente->value,
                'horas' => 25,
                'anios_experiencia' => 8,
                'anios_instituto' => 3,
                'contacto_emergencia' => 'Sofía Córdova',
                'contacto_num' => '0983326701',
            ],
            [
                'identificacion' => '0108453316',
                'nombres' => 'Paola Andrea',
                'apellidos' => 'Heredia Quizhpi',
                'genero_id' => 2,
                'fecha_nacimiento' => '1994-11-04',
                'parroquia' => 'El Vecino',
                'direccion' => 'Cdla. El Vecino, calle Los Pinos 6-14',
                'telefono1' => '072839770',
                'telefono2' => '0994802156',
                'email' => 'paola.heredia@siseduca.test',
                'contrato' => 'ocasional',
                'funcion' => 'Docente',
                'rol' => Role::Docente->value,
                'horas' => 20,
                'anios_experiencia' => 4,
                'anios_instituto' => 1,
                'contacto_emergencia' => 'Manuel Heredia',
                'contacto_num' => '0981679032',
            ],
        ];
    }
}
