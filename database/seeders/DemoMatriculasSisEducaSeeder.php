<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Alumno;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAula;
use App\Models\Padre;
use App\Models\Persona;
use App\Models\Sys_Provincia;
use App\Models\User;
use App\Services\OrganizacionAcademica;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role as SpatieRole;

class DemoMatriculasSisEducaSeeder extends Seeder
{
    private const CODIGO_AMIE = '01AS01061';

    private const FECHA_MATRICULA = '2026-09-01';

    /**
     * Matricula cinco estudiantes en cada aula que ya tiene docente y horario.
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
                throw new RuntimeException('Falta la provincia Azuay para las matrículas de demostración.');
            }

            $rolPadre = SpatieRole::findByName(Role::Padre->value, 'web');
            $rolAlumno = SpatieRole::findByName(Role::Alumno->value, 'web');
            $organizacion = app(OrganizacionAcademica::class);
            $aulas = EstablecimientoAula::query()
                ->where('establecimiento_id', $establecimiento->id)
                ->whereHas('periodo', fn ($query) => $query->where('activo', true))
                ->whereHas('asignaciones')
                ->whereExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('horario_clases')
                        ->whereColumn('horario_clases.establecimiento_aula_id', 'establecimiento_aulas.id');
                })
                ->with('establecimientoGrado.grado')
                ->orderBy('establecimiento_grado_id')
                ->orderBy('paralelo')
                ->get();

            foreach ($aulas as $aula) {
                $clave = $aula->establecimientoGrado?->grado?->nombre.'|'.$aula->paralelo;
                $curso = $this->cursos()[$clave] ?? null;

                if ($curso === null) {
                    throw new RuntimeException('No hay estudiantes de demostración para '.$clave.'.');
                }

                foreach ($curso as $estudiante) {
                    $alumno = $this->alumno($estudiante, $establecimiento, $provincia, $rolPadre, $rolAlumno);
                    $organizacion->matricular($aula, $alumno, self::FECHA_MATRICULA);
                }
            }
        });
    }

    /**
     * @param  array{padre: array{identificacion: string, nombres: string, apellidos: string, genero_id: int, fecha_nacimiento: string, estado_civil: string, titulo: string, parroquia: string, direccion: string, telefono1: string, telefono2: string, email: string}, identificacion: string, nombres: string, apellidos: string, genero_id: int, fecha_nacimiento: string, email: string}  $datos
     */
    private function alumno(
        array $datos,
        Establecimiento $establecimiento,
        Sys_Provincia $provincia,
        SpatieRole $rolPadre,
        SpatieRole $rolAlumno,
    ): Alumno {
        $padre = $this->padre($datos['padre'], $establecimiento, $provincia, $rolPadre);
        $persona = $this->persona(
            $datos,
            $datos['padre'],
            $establecimiento,
            $provincia,
            $rolAlumno,
            $datos['fecha_nacimiento'],
        );

        return Alumno::query()->updateOrCreate(
            ['persona_id' => $persona->id],
            [
                'padre_id' => $padre->id,
                'contacto_emergencia' => trim($datos['padre']['nombres'].' '.$datos['padre']['apellidos'].' '.$datos['padre']['telefono2']),
                'usuario' => 'demostracion',
                'id_estructura_form_matricula' => 0,
                'activo' => 1,
            ],
        );
    }

    /**
     * @param  array{identificacion: string, nombres: string, apellidos: string, genero_id: int, fecha_nacimiento: string, estado_civil: string, titulo: string, parroquia: string, direccion: string, telefono1: string, telefono2: string, email: string}  $datos
     */
    private function padre(array $datos, Establecimiento $establecimiento, Sys_Provincia $provincia, SpatieRole $rol): Padre
    {
        $persona = $this->persona($datos, $datos, $establecimiento, $provincia, $rol, $datos['fecha_nacimiento']);

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
     * @param  array{identificacion: string, nombres: string, apellidos: string, genero_id: int, email: string}  $datos
     * @param  array{parroquia: string, direccion: string, telefono1: string, telefono2: string}  $hogar
     */
    private function persona(
        array $datos,
        array $hogar,
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
                'parroquia' => $hogar['parroquia'],
                'direccion' => $hogar['direccion'],
                'telefono1' => $hogar['telefono1'],
                'telefono2' => $hogar['telefono2'],
                'nacionalidad_id' => $provincia->pais_id,
                'usuario' => 'demostracion',
                'activo' => 1,
                'establecimiento_id' => $establecimiento->id,
            ],
        );
    }

    /**
     * @param  array{identificacion: string, nombres: string, apellidos: string, genero_id: int, fecha_nacimiento: string, estado_civil: string, titulo: string, parroquia: string, direccion: string, telefono1: string, telefono2: string, email: string}  $padre
     * @param  array{identificacion: string, nombres: string, apellidos: string, genero_id: int, fecha_nacimiento: string, email: string}  $estudiante
     * @return array{padre: array{identificacion: string, nombres: string, apellidos: string, genero_id: int, fecha_nacimiento: string, estado_civil: string, titulo: string, parroquia: string, direccion: string, telefono1: string, telefono2: string, email: string}, identificacion: string, nombres: string, apellidos: string, genero_id: int, fecha_nacimiento: string, email: string}
     */
    private function estudiante(array $padre, array $estudiante): array
    {
        return [...$estudiante, 'padre' => $padre];
    }

    /**
     * @return array<string, list<array{padre: array{identificacion: string, nombres: string, apellidos: string, genero_id: int, fecha_nacimiento: string, estado_civil: string, titulo: string, parroquia: string, direccion: string, telefono1: string, telefono2: string, email: string}, identificacion: string, nombres: string, apellidos: string, genero_id: int, fecha_nacimiento: string, email: string}>>
     */
    private function cursos(): array
    {
        $arevalo = $this->padreDatos('0104410101', 'Carlos Eduardo', 'Arévalo Palacios', 1, '1981-06-18', 'Casado', 'Contador', 'El Batán', 'Calle Los Cipreses 4-20', '072814220', '0993102201', 'carlos.arevalo@siseduca.test');
        $munoz = $this->padreDatos('0104410102', 'Lorena Isabel', 'Muñoz Cabrera', 2, '1986-02-09', 'Casado', 'Docente', 'Totoracocha', 'Barrio Totoracocha, calle de los Cerezos 3-11', '072851104', '0994203302', 'lorena.munoz@siseduca.test');
        $delgado = $this->padreDatos('0104410103', 'Jorge Luis', 'Delgado Pinos', 1, '1979-11-27', 'Casado', 'Arquitecto', 'El Sagrario', 'Calle Larga 9-18 y Hermano Miguel', '072820331', '0983314403', 'jorge.delgado@siseduca.test');
        $torres = $this->padreDatos('0104410104', 'Andrea Carolina', 'Torres Vega', 2, '1988-08-03', 'Unión libre', 'Enfermera', 'Yanuncay', 'Av. Loja 8-40 y Del Tejar', '072809214', '0992145504', 'andrea.torres@siseduca.test');
        $pinos = $this->padreDatos('0104410105', 'Fernando José', 'Pinos Torres', 1, '1983-04-14', 'Casado', 'Comerciante', 'San Blas', 'Calle Bolívar 5-22 y Benigno Malo', '072821670', '0986706605', 'fernando.pinos@siseduca.test');
        $vega = $this->padreDatos('0104410106', 'Mónica Alejandra', 'Vega Morales', 2, '1990-01-22', 'Soltero', 'Administradora', 'Huayna Cápac', 'Av. Huayna Cápac 1-55', '072836902', '0999027706', 'monica.vega@siseduca.test');
        $flores = $this->padreDatos('0104410107', 'Raúl Esteban', 'Flores Herrera', 1, '1978-09-30', 'Casado', 'Técnico electricista', 'Cañaribamba', 'Av. De las Américas y Unidad Nacional', '072847118', '0981188807', 'raul.flores@siseduca.test');
        $castillo = $this->padreDatos('0104410108', 'Patricia Beatriz', 'Castillo Romero', 2, '1984-12-05', 'Divorciado', 'Abogada', 'San Sebastián', 'Calle Gran Colombia 6-33', '072833441', '0994419908', 'patricia.castillo@siseduca.test');
        $herrera = $this->padreDatos('0104410109', 'Rosa Elena', 'Herrera Castillo', 2, '1982-07-19', 'Casado', 'Comerciante', 'El Vergel', 'Av. Solano 6-12 y Remigio Crespo', '072845660', '0996601010', 'rosa.herrera@siseduca.test');
        $aguilar = $this->padreDatos('0104410110', 'Iván Marcelo', 'Aguilar Benítez', 1, '1976-03-11', 'Casado', 'Contador', 'Bellavista', 'Cdla. Bellavista, calle Miguel Moreno 2-08', '072842901', '0989011111', 'ivan.aguilar@siseduca.test');
        $romero = $this->padreDatos('0104410111', 'Gabriela Nohemí', 'Romero Aguilar', 2, '1987-05-28', 'Casado', 'Docente', 'Monay', 'Av. González Suárez 4-19', '072858220', '0992201212', 'gabriela.romero@siseduca.test');
        $pazmino = $this->padreDatos('0104410112', 'Diego Alejandro', 'Pazmiño Ochoa', 1, '1985-10-02', 'Unión libre', 'Arquitecto', 'Machángara', 'Barrio Machángara, calle Tarqui 7-14', '072863774', '0997741313', 'diego.pazmino@siseduca.test');
        $serrano = $this->padreDatos('0104410113', 'Luis Alberto', 'Serrano Valdivieso', 1, '1980-01-16', 'Casado', 'Ingeniero comercial', 'Gil Ramírez Dávalos', 'Calle Tarqui 2-41 y Vega Muñoz', '072818905', '0989051414', 'luis.serrano@siseduca.test');
        $carrasco = $this->padreDatos('0104410114', 'Paula Andrea', 'Carrasco Benítez', 2, '1989-06-07', 'Soltero', 'Enfermera', 'Sucre', 'Calle Sucre 1-28 y Coronel Talbot', '072826440', '0994401515', 'paula.carrasco@siseduca.test');
        $ochoa = $this->padreDatos('0104410115', 'Andrés Felipe', 'Ochoa Pazmiño', 1, '1977-08-25', 'Casado', 'Técnico electricista', 'El Vecino', 'Cdla. El Vecino, calle Los Pinos 3-06', '072839118', '0981181616', 'andres.ochoa@siseduca.test');
        $morales = $this->padreDatos('0104410116', 'Regina Antonella', 'Morales Flores', 2, '1991-11-13', 'Unión libre', 'Administradora', 'Totoracocha', 'Calle Paseo de los Cañaris N6-22', '072851773', '0997731717', 'regina.morales@siseduca.test');
        $bermeo = $this->padreDatos('0104410117', 'Héctor Fabián', 'Bermeo Jara', 1, '1975-02-04', 'Casado', 'Comerciante', 'El Batán', 'Calle De los Álamos 8-15', '072814908', '0989081818', 'hector.bermeo@siseduca.test');
        $cueva = $this->padreDatos('0104410118', 'Natalia Soledad', 'Cueva Tapia', 2, '1983-09-21', 'Casado', 'Abogada', 'San Blas', 'Calle Presidente Córdova 7-09', '072821334', '0993341919', 'natalia.cueva@siseduca.test');
        $beltran = $this->padreDatos('0104410119', 'Sebastián David', 'Beltrán Vélez', 1, '1979-12-17', 'Divorciado', 'Contador', 'Yanuncay', 'Av. Del Tejar 11-30', '072809662', '0986622020', 'sebastian.beltran@siseduca.test');
        $leon = $this->padreDatos('0104410120', 'Kevin Andrés', 'León Brito', 1, '1986-04-08', 'Casado', 'Docente', 'Huayna Cápac', 'Av. Paseo de los Cañaris 2-77', '072836115', '0991152121', 'kevin.leon@siseduca.test');
        $rojas = $this->padreDatos('0104410121', 'Gladys Patricia', 'Rojas Dután', 2, '1981-07-29', 'Casado', 'Enfermera', 'El Vergel', 'Calle Remigio Crespo 3-44', '072845019', '0990192222', 'gladys.rojas@siseduca.test');
        $mendez = $this->padreDatos('0104410122', 'Fernando José', 'Méndez Suárez', 1, '1974-05-06', 'Casado', 'Arquitecto', 'Cañaribamba', 'Cdla. El Paraíso, pasaje Las Gardenias 1-18', '072847552', '0985522323', 'fernando.mendez@siseduca.test');
        $vintimilla = $this->padreDatos('0104410123', 'Cristina Alejandra', 'Vintimilla Córdova', 2, '1988-03-15', 'Soltero', 'Administradora', 'Sucre', 'Calle Coronel Talbot 5-61', '072826903', '0999032424', 'cristina.vintimilla@siseduca.test');
        $quizhpi = $this->padreDatos('0104410124', 'Manuel Esteban', 'Quizhpi Arévalo', 1, '1982-10-23', 'Unión libre', 'Comerciante', 'Machángara', 'Calle Paseo Río Machángara N1-09', '072863441', '0994412525', 'manuel.quizhpi@siseduca.test');

        return [
            'Quinto de EGB|A' => [
                $this->estudiante($arevalo, ['identificacion' => '0106610101', 'nombres' => 'Tomás Andrés', 'apellidos' => 'Arévalo Palacios', 'genero_id' => 1, 'fecha_nacimiento' => '2016-03-12', 'email' => 'tomas.arevalo@siseduca.test']),
                $this->estudiante($arevalo, ['identificacion' => '0106610102', 'nombres' => 'Valentina Nicole', 'apellidos' => 'Arévalo Palacios', 'genero_id' => 2, 'fecha_nacimiento' => '2016-03-12', 'email' => 'valentina.arevalo@siseduca.test']),
                $this->estudiante($munoz, ['identificacion' => '0106610103', 'nombres' => 'Matías Gabriel', 'apellidos' => 'Muñoz Cabrera', 'genero_id' => 1, 'fecha_nacimiento' => '2016-07-04', 'email' => 'matias.munoz@siseduca.test']),
                $this->estudiante($delgado, ['identificacion' => '0106610104', 'nombres' => 'Sofía Elena', 'apellidos' => 'Delgado Pinos', 'genero_id' => 2, 'fecha_nacimiento' => '2016-11-21', 'email' => 'sofia.delgado@siseduca.test']),
                $this->estudiante($torres, ['identificacion' => '0106610105', 'nombres' => 'Diego Alejandro', 'apellidos' => 'Torres Vega', 'genero_id' => 1, 'fecha_nacimiento' => '2015-09-30', 'email' => 'diego.torres@siseduca.test']),
            ],
            'Quinto de EGB|B' => [
                $this->estudiante($pinos, ['identificacion' => '0106610106', 'nombres' => 'Emiliano José', 'apellidos' => 'Pinos Torres', 'genero_id' => 1, 'fecha_nacimiento' => '2016-01-18', 'email' => 'emiliano.pinos@siseduca.test']),
                $this->estudiante($pinos, ['identificacion' => '0106610107', 'nombres' => 'Camila Renata', 'apellidos' => 'Pinos Torres', 'genero_id' => 2, 'fecha_nacimiento' => '2016-01-18', 'email' => 'camila.pinos@siseduca.test']),
                $this->estudiante($vega, ['identificacion' => '0106610108', 'nombres' => 'Juan Sebastián', 'apellidos' => 'Vega Morales', 'genero_id' => 1, 'fecha_nacimiento' => '2016-05-09', 'email' => 'juan.vega@siseduca.test']),
                $this->estudiante($flores, ['identificacion' => '0106610109', 'nombres' => 'Ana Lucía', 'apellidos' => 'Flores Herrera', 'genero_id' => 2, 'fecha_nacimiento' => '2015-12-02', 'email' => 'ana.flores@siseduca.test']),
                $this->estudiante($castillo, ['identificacion' => '0106610110', 'nombres' => 'Martín Eduardo', 'apellidos' => 'Castillo Romero', 'genero_id' => 1, 'fecha_nacimiento' => '2016-08-27', 'email' => 'martin.castillo@siseduca.test']),
            ],
            'Octavo de EGB|A' => [
                $this->estudiante($herrera, ['identificacion' => '0106610111', 'nombres' => 'Nicolás Alejandro', 'apellidos' => 'Herrera Castillo', 'genero_id' => 1, 'fecha_nacimiento' => '2013-02-14', 'email' => 'nicolas.herrera@siseduca.test']),
                $this->estudiante($herrera, ['identificacion' => '0106610112', 'nombres' => 'Daniela Estefanía', 'apellidos' => 'Herrera Castillo', 'genero_id' => 2, 'fecha_nacimiento' => '2013-02-14', 'email' => 'daniela.herrera@siseduca.test']),
                $this->estudiante($aguilar, ['identificacion' => '0106610113', 'nombres' => 'Joaquín Andrés', 'apellidos' => 'Aguilar Benítez', 'genero_id' => 1, 'fecha_nacimiento' => '2013-06-19', 'email' => 'joaquin.aguilar@siseduca.test']),
                $this->estudiante($romero, ['identificacion' => '0106610114', 'nombres' => 'Isabella María', 'apellidos' => 'Romero Aguilar', 'genero_id' => 2, 'fecha_nacimiento' => '2012-10-08', 'email' => 'isabella.romero@siseduca.test']),
                $this->estudiante($pazmino, ['identificacion' => '0106610115', 'nombres' => 'Gabriel Esteban', 'apellidos' => 'Pazmiño Ochoa', 'genero_id' => 1, 'fecha_nacimiento' => '2013-04-25', 'email' => 'gabriel.pazmino@siseduca.test']),
            ],
            'Octavo de EGB|B' => [
                $this->estudiante($serrano, ['identificacion' => '0106610116', 'nombres' => 'Santiago David', 'apellidos' => 'Serrano Valdivieso', 'genero_id' => 1, 'fecha_nacimiento' => '2013-08-03', 'email' => 'santiago.serrano@siseduca.test']),
                $this->estudiante($serrano, ['identificacion' => '0106610117', 'nombres' => 'Emilia Renata', 'apellidos' => 'Serrano Valdivieso', 'genero_id' => 2, 'fecha_nacimiento' => '2013-08-03', 'email' => 'emilia.serrano@siseduca.test']),
                $this->estudiante($carrasco, ['identificacion' => '0106610118', 'nombres' => 'Doménica Isabel', 'apellidos' => 'Carrasco Benítez', 'genero_id' => 2, 'fecha_nacimiento' => '2012-11-16', 'email' => 'domenica.carrasco@siseduca.test']),
                $this->estudiante($ochoa, ['identificacion' => '0106610119', 'nombres' => 'Felipe Andrés', 'apellidos' => 'Ochoa Pazmiño', 'genero_id' => 1, 'fecha_nacimiento' => '2013-01-29', 'email' => 'felipe.ochoa@siseduca.test']),
                $this->estudiante($morales, ['identificacion' => '0106610120', 'nombres' => 'Antonella Isabel', 'apellidos' => 'Morales Flores', 'genero_id' => 2, 'fecha_nacimiento' => '2013-09-07', 'email' => 'antonella.morales@siseduca.test']),
            ],
            'Primero de Bachillerato|A' => [
                $this->estudiante($bermeo, ['identificacion' => '0106610121', 'nombres' => 'Ricardo Daniel', 'apellidos' => 'Bermeo Jara', 'genero_id' => 1, 'fecha_nacimiento' => '2010-05-11', 'email' => 'ricardo.bermeo@siseduca.test']),
                $this->estudiante($bermeo, ['identificacion' => '0106610122', 'nombres' => 'Adriana Cristina', 'apellidos' => 'Bermeo Jara', 'genero_id' => 2, 'fecha_nacimiento' => '2010-05-11', 'email' => 'adriana.bermeo@siseduca.test']),
                $this->estudiante($cueva, ['identificacion' => '0106610123', 'nombres' => 'Mateo Josué', 'apellidos' => 'Cueva Tapia', 'genero_id' => 1, 'fecha_nacimiento' => '2010-09-02', 'email' => 'mateo.cueva@siseduca.test']),
                $this->estudiante($beltran, ['identificacion' => '0106610124', 'nombres' => 'Natalia Soledad', 'apellidos' => 'Beltrán Vélez', 'genero_id' => 2, 'fecha_nacimiento' => '2011-01-20', 'email' => 'natalia.beltran@siseduca.test']),
                $this->estudiante($leon, ['identificacion' => '0106610125', 'nombres' => 'Mateo Andrés', 'apellidos' => 'León Brito', 'genero_id' => 1, 'fecha_nacimiento' => '2010-12-14', 'email' => 'mateo.leon@siseduca.test']),
            ],
            'Primero de Bachillerato|B' => [
                $this->estudiante($rojas, ['identificacion' => '0106610126', 'nombres' => 'José Miguel', 'apellidos' => 'Rojas Dután', 'genero_id' => 1, 'fecha_nacimiento' => '2010-04-06', 'email' => 'jose.rojas@siseduca.test']),
                $this->estudiante($rojas, ['identificacion' => '0106610127', 'nombres' => 'María Fernanda', 'apellidos' => 'Rojas Dután', 'genero_id' => 2, 'fecha_nacimiento' => '2010-04-06', 'email' => 'maria.rojas@siseduca.test']),
                $this->estudiante($mendez, ['identificacion' => '0106610128', 'nombres' => 'José Fernando', 'apellidos' => 'Méndez Suárez', 'genero_id' => 1, 'fecha_nacimiento' => '2011-03-18', 'email' => 'jose.mendez@siseduca.test']),
                $this->estudiante($vintimilla, ['identificacion' => '0106610129', 'nombres' => 'María Cristina', 'apellidos' => 'Vintimilla Córdova', 'genero_id' => 2, 'fecha_nacimiento' => '2010-08-23', 'email' => 'maria.vintimilla@siseduca.test']),
                $this->estudiante($quizhpi, ['identificacion' => '0106610130', 'nombres' => 'Diego Alejandro', 'apellidos' => 'Quizhpi Arévalo', 'genero_id' => 1, 'fecha_nacimiento' => '2010-11-09', 'email' => 'diego.quizhpi@siseduca.test']),
            ],
        ];
    }

    /**
     * @return array{identificacion: string, nombres: string, apellidos: string, genero_id: int, fecha_nacimiento: string, estado_civil: string, titulo: string, parroquia: string, direccion: string, telefono1: string, telefono2: string, email: string}
     */
    private function padreDatos(
        string $identificacion,
        string $nombres,
        string $apellidos,
        int $generoId,
        string $fechaNacimiento,
        string $estadoCivil,
        string $titulo,
        string $parroquia,
        string $direccion,
        string $telefono1,
        string $telefono2,
        string $email,
    ): array {
        return [
            'identificacion' => $identificacion,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'genero_id' => $generoId,
            'fecha_nacimiento' => $fechaNacimiento,
            'estado_civil' => $estadoCivil,
            'titulo' => $titulo,
            'parroquia' => $parroquia,
            'direccion' => $direccion,
            'telefono1' => $telefono1,
            'telefono2' => $telefono2,
            'email' => $email,
        ];
    }
}
