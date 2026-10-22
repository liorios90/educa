<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\Padre;
use App\Models\Persona;
use App\Models\Sys_Funcion;
use App\Models\Sys_Provincia;
use App\Models\Sys_TipoContrato;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as SpatieRole;

class DemoMantenimientoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        DB::transaction(function (): void {
            $establecimiento = Establecimiento::query()
                ->where('codigo_amie', '01H-F001')
                ->firstOrFail();
            $provincia = Sys_Provincia::query()->firstOrFail();
            $funcionDocente = Sys_Funcion::query()->firstOrCreate(
                ['nombre' => 'Docente'],
                ['descripcion' => 'Docente', 'usuario' => 'seeder-demo', 'activo' => 1],
            );
            $tipoContrato = Sys_TipoContrato::query()->firstOrCreate(
                ['codigo' => 'DEMO'],
                [
                    'nombre' => 'Contrato demostrativo',
                    'descripcion' => 'Contrato utilizado por el conjunto de datos de demostración.',
                    'usuario' => 'seeder-demo',
                    'activo' => 1,
                ],
            );

            $roles = [
                'Padre' => SpatieRole::findByName(Role::Padre->value, 'web'),
                'Alumno' => SpatieRole::findByName(Role::Alumno->value, 'web'),
                'Docente' => SpatieRole::findByName(Role::Docente->value, 'web'),
            ];

            for ($numero = 1; $numero <= 15; $numero++) {
                $sufijo = str_pad((string) $numero, 2, '0', STR_PAD_LEFT);

                $padreUser = $this->usuarioDemo("padre.{$sufijo}", "Padre Demo {$sufijo}", $establecimiento, $roles['Padre']);
                $padrePersona = $this->personaDemo(
                    '17770000'.$sufijo,
                    'Representante '.$sufijo,
                    'Demo Educa',
                    '1978-01-01',
                    $establecimiento,
                    $provincia,
                    $padreUser,
                );
                $padre = Padre::query()->updateOrCreate(
                    ['persona_id' => $padrePersona->id],
                    [
                        'estado_civil_id' => 'casado',
                        'vive_con_estudiante' => true,
                        'titulo' => 'Representante',
                        'usuario' => 'demo-padre-'.$sufijo,
                        'activo' => 1,
                    ],
                );

                $alumnoUser = $this->usuarioDemo("alumno.{$sufijo}", "Alumno Demo {$sufijo}", $establecimiento, $roles['Alumno']);
                $alumnoPersona = $this->personaDemo(
                    '17880000'.$sufijo,
                    'Estudiante '.$sufijo,
                    'Demo Educa',
                    '2010-01-01',
                    $establecimiento,
                    $provincia,
                    $alumnoUser,
                );
                Alumno::query()->updateOrCreate(
                    ['persona_id' => $alumnoPersona->id],
                    [
                        'padre_id' => $padre->id,
                        'contacto_emergencia' => 'Representante '.$sufijo,
                        'usuario' => 'demo-alumno-'.$sufijo,
                        'id_estructura_form_matricula' => 0,
                        'activo' => 1,
                    ],
                );

                $docenteUser = $this->usuarioDemo("docente.{$sufijo}", "Docente Demo {$sufijo}", $establecimiento, $roles['Docente']);
                $docentePersona = $this->personaDemo(
                    '17990000'.$sufijo,
                    'Docente '.$sufijo,
                    'Demo Educa',
                    '1985-01-01',
                    $establecimiento,
                    $provincia,
                    $docenteUser,
                );
                Empleado::query()->updateOrCreate(
                    ['persona_id' => $docentePersona->id],
                    [
                        'tipo_contrato_id' => $tipoContrato->id,
                        'cargo_id' => null,
                        'funcion_id' => $funcionDocente->id,
                        'horas' => 40,
                        'anios_experiencia' => 2 + ($numero % 10),
                        'anios_instituto' => 1,
                        'contacto_emergencia' => 'Contacto docente '.$sufijo,
                        'contacto_num' => '09900000'.$sufijo,
                        'usuario' => 'demo-docente-'.$sufijo,
                        'activo' => 1,
                    ],
                );
            }
        });
    }

    private function usuarioDemo(string $identificador, string $nombre, Establecimiento $establecimiento, SpatieRole $rol): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => "{$identificador}@educa.test"],
            [
                'name' => $nombre,
                'password' => 'password',
                'establecimiento_id' => $establecimiento->id,
            ],
        );
        $user->syncRoles([$rol]);

        return $user;
    }

    private function personaDemo(
        string $identificacion,
        string $nombres,
        string $apellidos,
        string $fechaNacimiento,
        Establecimiento $establecimiento,
        Sys_Provincia $provincia,
        User $user,
    ): Persona {
        return Persona::query()->updateOrCreate(
            ['identificacion' => $identificacion],
            [
                'user_id' => $user->id,
                'tipo_identificacion_id' => 1,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'genero_id' => 1,
                'fecha_nacimiento' => $fechaNacimiento,
                'ciudad_nacimiento' => $provincia->nombre,
                'provincia_id' => $provincia->id,
                'parroquia' => 'Centro',
                'direccion' => 'Dirección de demostración '.$identificacion,
                'telefono1' => '020000000',
                'telefono2' => '0990000000',
                'nacionalidad_id' => $provincia->pais_id,
                'usuario' => 'demo-'.$identificacion,
                'activo' => 1,
                'establecimiento_id' => $establecimiento->id,
            ],
        );
    }
}
