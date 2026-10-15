<?php

namespace Database\Seeders;

use App\Enums\ModoLibretaArea;
use App\Enums\Role;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAreaLibreta;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoNivel;
use App\Models\EstablecimientoPeriodo;
use App\Models\EstablecimientoSubnivel;
use App\Models\HorarioClase;
use App\Models\Padre;
use App\Models\Persona;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Circuito;
use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
use App\Models\Sys_Nivel;
use App\Models\Sys_Provincia;
use App\Models\Sys_TipoContrato;
use App\Models\TipoInsumo;
use App\Models\TipoInsumoDetalle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as SpatieRole;

class DemoInstitutosSeeder extends Seeder
{
    /**
     * Create a repeatable demo dataset without removing existing catalog or school data.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            EstructuraEducativaSeeder::class,
            CurriculoEducativoSeeder::class,
            ModalidadJornadaSeeder::class,
        ]);

        DB::transaction(function (): void {
            $provincia = Sys_Provincia::query()->first() ?? Sys_Provincia::factory()->create();
            $circuito = Sys_Circuito::query()->first()
                ?? Sys_Circuito::factory()->create(['nombre' => 'Circuito de demostración']);
            $contrato = Sys_TipoContrato::query()->firstOrCreate(
                ['codigo' => 'DEMO'],
                [
                    'nombre' => 'Contrato demostrativo',
                    'descripcion' => 'Contrato utilizado por el conjunto de datos de demostración.',
                    'usuario' => 'seeder-demo',
                    'activo' => 1,
                ],
            );

            $tiposInsumos = $this->crearTiposInsumos();
            $niveles = Sys_Nivel::query()->with(['subniveles.grados', 'subniveles.areas.asignaturas'])->get();
            $modalidades = Sys_Modalidad::query()->orderBy('id')->get()->values();
            $jornadas = Sys_Jornada::query()->orderBy('id')->get()->values();
            $adminRole = SpatieRole::findByName(Role::Admin->value, 'web');
            $docenteRole = SpatieRole::findByName(Role::Docente->value, 'web');
            $especializaciones = Sys_Asignatura::query()->pluck('id')->all();

            foreach ($this->institutos() as $indice => $institutoData) {
                $establecimiento = Establecimiento::query()->updateOrCreate(
                    ['codigo_amie' => $institutoData['codigo_amie']],
                    [
                        'nombre' => $institutoData['nombre'],
                        'descripcion' => 'Instituto de demostración para pruebas del sistema.',
                        'direccion' => 'Av. Educativa '.($indice + 1),
                        'telefono' => '02000000'.($indice + 1),
                        'representante' => 'Representante Demo '.($indice + 1),
                        'regimen' => $indice % 2 === 0 ? 'Sierra' : 'Costa',
                        'email' => $institutoData['email'],
                        'usuario' => 'instituto-demo-'.str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT),
                        'activo' => 1,
                        'logo' => 'logo.png',
                        'zona_id' => $circuito->distrito->zona_id,
                        'distrito_id' => $circuito->distrito_id,
                        'circuito_id' => $circuito->id,
                    ],
                );

                $this->crearAdministrador($establecimiento, $institutoData, $adminRole->id);

                $modalidad = $modalidades[$indice % $modalidades->count()];
                $jornada = $jornadas[$indice % $jornadas->count()];
                $establecimientoModalidad = EstablecimientoModalidad::query()->updateOrCreate(
                    [
                        'establecimiento_id' => $establecimiento->id,
                        'modalidad_id' => $modalidad->id,
                    ],
                );
                $oferta = EstablecimientoModalidadJornada::query()->updateOrCreate(
                    [
                        'establecimiento_modalidad_id' => $establecimientoModalidad->id,
                        'jornada_id' => $jornada->id,
                    ],
                );

                $periodo = EstablecimientoPeriodo::query()->updateOrCreate(
                    [
                        'establecimiento_modalidad_jornada_id' => $oferta->id,
                        'nombre' => 'Periodo demostrativo 2026-2027',
                    ],
                    [
                        'establecimiento_id' => $establecimiento->id,
                        'fecha_inicio' => '2026-09-01',
                        'fecha_fin' => '2027-06-30',
                        'activo' => true,
                    ],
                );

                $empleados = $this->crearDocentes(
                    $establecimiento,
                    $provincia,
                    $contrato->id,
                    $docenteRole->id,
                    $especializaciones,
                );
                $horaInicioJornada = match ($jornada->nombre) {
                    'Vespertina' => 12,
                    'Nocturna' => 17,
                    'Otro' => 8,
                    default => 7,
                };
                fake()->seed($establecimiento->id);
                $this->crearEstructuraYOferta(
                    $establecimiento,
                    $oferta,
                    $periodo,
                    $niveles,
                    $empleados,
                    $tiposInsumos,
                    $horaInicioJornada,
                    $especializaciones,
                );
                $this->crearAlumnos($establecimiento, $provincia, $indice + 1);
            }
        });
    }

    /**
     * @return list<array{nombre: string, codigo_amie: string, email: string, admin_email: string}>
     */
    private function institutos(): array
    {
        $institutos = [];

        for ($numero = 1; $numero <= 10; $numero++) {
            $sufijo = str_pad((string) $numero, 2, '0', STR_PAD_LEFT);
            $institutos[] = [
                'nombre' => 'Instituto de Demostración '.$sufijo,
                'codigo_amie' => '17HDEMO'.$sufijo,
                'email' => 'instituto'.$sufijo.'@demo.educa.test',
                'admin_email' => 'admin'.$sufijo.'@demo.educa.test',
            ];
        }

        return $institutos;
    }

    private function crearAdministrador(Establecimiento $establecimiento, array $institutoData, int $adminRoleId): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => $institutoData['admin_email']],
            [
                'name' => 'Administrador '.$establecimiento->nombre,
                'password' => 'password',
                'establecimiento_id' => $establecimiento->id,
            ],
        );

        $user->roles()->sync([$adminRoleId]);
    }

    /**
     * @return list<Empleado>
     */
    private function crearDocentes(
        Establecimiento $establecimiento,
        Sys_Provincia $provincia,
        int $contratoId,
        int $docenteRoleId,
        array $especializaciones,
    ): array {
        $docentes = [];

        for ($numero = 1; $numero <= 5; $numero++) {
            $identificacion = '179900'.str_pad((string) $establecimiento->id, 3, '0', STR_PAD_LEFT).$numero;
            $persona = $this->personaDemo(
                $identificacion,
                'Docente '.$numero,
                $establecimiento->nombre,
                $establecimiento,
                $provincia,
            );

            $docentes[] = Empleado::query()->updateOrCreate(
                ['persona_id' => $persona->id],
                [
                    'tipo_contrato_id' => $contratoId,
                    'cargo_id' => null,
                    'funcion_id' => null,
                    'horas' => 40,
                    'anios_experiencia' => $numero + 1,
                    'anios_instituto' => 1,
                    'contacto_emergencia' => 'Contacto docente '.$numero,
                    'contacto_num' => '09900000'.str_pad((string) $numero, 2, '0', STR_PAD_LEFT),
                    'usuario' => 'docente-'.$establecimiento->id.'-'.$numero,
                    'activo' => 1,
                ],
            );

            $user = User::query()->updateOrCreate(
                ['email' => 'docente-'.$establecimiento->id.'-'.$numero.'@demo.educa.test'],
                [
                    'name' => $persona->nombres.' '.$persona->apellidos,
                    'password' => 'password',
                    'establecimiento_id' => $establecimiento->id,
                ],
            );

            $user->roles()->sync([$docenteRoleId]);
            $persona->update(['user_id' => $user->id]);
            $docentes[array_key_last($docentes)]->especialidades()->sync($especializaciones);
        }

        return $docentes;
    }

    /**
     * @param  Collection<int, Sys_Nivel>  $niveles
     * @param  list<Empleado>  $docentes
     * @param  list<TipoInsumo>  $tiposInsumos
     * @param  list<int>  $especializaciones
     */
    private function crearEstructuraYOferta(
        Establecimiento $establecimiento,
        EstablecimientoModalidadJornada $oferta,
        EstablecimientoPeriodo $periodo,
        $niveles,
        array $docentes,
        array $tiposInsumos,
        int $horaInicioJornada,
        array $especializaciones,
    ): void {
        $horariosOcupadosPorDocente = [];
        $horariosOcupadosPorAula = [];
        $cargaPorDocente = [];

        foreach ($niveles as $nivel) {
            EstablecimientoNivel::query()->updateOrCreate(
                [
                    'establecimiento_modalidad_jornada_id' => $oferta->id,
                    'nivel_id' => $nivel->id,
                ],
                ['establecimiento_id' => $establecimiento->id],
            );

            foreach ($nivel->subniveles as $subnivel) {
                EstablecimientoSubnivel::query()->updateOrCreate(
                    [
                        'establecimiento_modalidad_jornada_id' => $oferta->id,
                        'subnivel_id' => $subnivel->id,
                    ],
                    [
                        'establecimiento_id' => $establecimiento->id,
                        'nivel_id' => $nivel->id,
                        'nombre' => $subnivel->nombre,
                    ],
                );

                foreach ($subnivel->grados as $grado) {
                    $gradoEstablecimiento = EstablecimientoGrado::query()->updateOrCreate(
                        [
                            'establecimiento_modalidad_jornada_id' => $oferta->id,
                            'grado_id' => $grado->id,
                        ],
                        [
                            'establecimiento_id' => $establecimiento->id,
                            'subnivel_id' => $subnivel->id,
                            'nombre' => $grado->nombre,
                        ],
                    );

                    $aula = EstablecimientoAula::query()->updateOrCreate(
                        [
                            'establecimiento_periodo_id' => $periodo->id,
                            'establecimiento_grado_id' => $gradoEstablecimiento->id,
                            'paralelo' => 'A',
                        ],
                        [
                            'establecimiento_id' => $establecimiento->id,
                            'establecimiento_modalidad_jornada_id' => $oferta->id,
                        ],
                    );

                    $asignaturas = $grado->asignaturasCurriculares()->with('area')->get();

                    if ($asignaturas->isEmpty()) {
                        $asignaturas = $subnivel->areas->flatMap->asignaturas->unique('id')->values();
                    }

                    foreach ($asignaturas as $indiceAsignatura => $asignatura) {
                        $malla = EstablecimientoAsignatura::query()->updateOrCreate(
                            [
                                'establecimiento_modalidad_jornada_id' => $oferta->id,
                                'grado_id' => $grado->id,
                                'asignatura_id' => $asignatura->id,
                            ],
                            [
                                'establecimiento_id' => $establecimiento->id,
                                'aparece_en_libreta' => $asignatura->aparece_en_libreta,
                                'horas_semanales' => $asignatura->horas_semanales,
                                'tipo_calificacion' => $subnivel->tipo_calificacion,
                                'tipo_asignatura' => 'tronco_comun',
                                'califica_por_destrezas' => $subnivel->califica_por_destrezas,
                                'usa_tabla_equivalencias' => false,
                            ],
                        );

                        EstablecimientoAreaLibreta::query()->updateOrCreate(
                            [
                                'establecimiento_modalidad_jornada_id' => $oferta->id,
                                'grado_id' => $grado->id,
                                'area_id' => $asignatura->area_id,
                            ],
                            [
                                'establecimiento_id' => $establecimiento->id,
                                'modo_libreta' => ModoLibretaArea::Asignaturas,
                                'promedio_porcentual' => false,
                            ],
                        );

                        $docente = $this->seleccionarDocenteAleatorio(
                            $docentes,
                            $aula,
                            $horariosOcupadosPorDocente,
                            $horariosOcupadosPorAula,
                            $cargaPorDocente,
                        );
                        EstablecimientoAulaDocente::query()->updateOrCreate(
                            [
                                'establecimiento_aula_id' => $aula->id,
                                'establecimiento_asignatura_id' => $malla->id,
                            ],
                            ['empleado_id' => $docente->id],
                        );

                        $this->crearHorario(
                            $aula,
                            $malla,
                            $docente,
                            $horaInicioJornada,
                            $horariosOcupadosPorDocente,
                            $horariosOcupadosPorAula,
                        );

                        DB::table('establecimiento_aula_asignatura_tipo_insumos')->updateOrInsert(
                            [
                                'establecimiento_aula_id' => $aula->id,
                                'establecimiento_asignatura_id' => $malla->id,
                            ],
                            [
                                'tipo_insumo_id' => $tiposInsumos[$indiceAsignatura % count($tiposInsumos)]->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ],
                        );
                    }
                }
            }
        }
    }

    private function crearAlumnos(Establecimiento $establecimiento, Sys_Provincia $provincia, int $numeroInstituto): void
    {
        for ($numero = 1; $numero <= 10; $numero++) {
            $identificacion = '178800'.str_pad((string) $establecimiento->id, 3, '0', STR_PAD_LEFT).$numero;
            $persona = $this->personaDemo(
                $identificacion,
                'Estudiante '.$numero,
                'Instituto '.$numeroInstituto,
                $establecimiento,
                $provincia,
            );
            $identificacionPadre = '177700'.str_pad((string) $establecimiento->id, 3, '0', STR_PAD_LEFT).$numero;
            $personaPadre = $this->personaDemo(
                $identificacionPadre,
                'Representante '.$numero,
                'Instituto '.$numeroInstituto,
                $establecimiento,
                $provincia,
                '1975-01-01',
            );
            $padre = Padre::query()->updateOrCreate(
                ['persona_id' => $personaPadre->id],
                [
                    'estado_civil_id' => 'casado',
                    'vive_con_estudiante' => 1,
                    'titulo' => 'Representante',
                    'usuario' => 'padre-'.$establecimiento->id.'-'.$numero,
                    'activo' => 1,
                ],
            );

            Alumno::query()->updateOrCreate(
                ['persona_id' => $persona->id],
                [
                    'padre_id' => $padre->id,
                    'contacto_emergencia' => 'Representante '.$numero,
                    'usuario' => 'alumno-'.$establecimiento->id.'-'.$numero,
                    'id_estructura_form_matricula' => 0,
                    'activo' => 1,
                ],
            );
        }
    }

    private function personaDemo(
        string $identificacion,
        string $nombres,
        string $apellidos,
        Establecimiento $establecimiento,
        Sys_Provincia $provincia,
        string $fechaNacimiento = '2008-01-01',
    ): Persona {
        return Persona::query()->updateOrCreate(
            ['identificacion' => $identificacion],
            [
                'tipo_identificacion_id' => 1,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'genero_id' => 1,
                'fecha_nacimiento' => $fechaNacimiento,
                'ciudad_nacimiento' => $provincia->nombre,
                'provincia_id' => $provincia->id,
                'parroquia' => 'Centro',
                'direccion' => 'Dirección de demostración',
                'telefono1' => '020000000',
                'telefono2' => '0990000000',
                'nacionalidad_id' => $provincia->pais_id,
                'usuario' => strtolower(str_replace(' ', '.', $identificacion)),
                'activo' => 1,
                'establecimiento_id' => $establecimiento->id,
            ],
        );
    }

    /**
     * @param  array<int, array<int, array<int, bool>>>  $horariosOcupadosPorDocente
     * @param  array<int, array<int, array<int, bool>>>  $horariosOcupadosPorAula
     */
    private function crearHorario(
        EstablecimientoAula $aula,
        EstablecimientoAsignatura $malla,
        Empleado $docente,
        int $horaInicioJornada,
        array &$horariosOcupadosPorDocente,
        array &$horariosOcupadosPorAula,
    ): void {
        for ($dia = 1; $dia <= 5; $dia++) {
            for ($bloque = 0; $bloque < 7; $bloque++) {
                if (($horariosOcupadosPorDocente[$docente->id][$dia][$bloque] ?? false)
                    || ($horariosOcupadosPorAula[$aula->id][$dia][$bloque] ?? false)) {
                    continue;
                }

                $minutosInicio = ($horaInicioJornada * 60) + ($bloque * 60);
                $minutosFin = $minutosInicio + 45;
                $horaInicio = sprintf('%02d:%02d', intdiv($minutosInicio, 60), $minutosInicio % 60);
                $horaFin = sprintf('%02d:%02d', intdiv($minutosFin, 60), $minutosFin % 60);

                HorarioClase::query()->updateOrCreate(
                    [
                        'establecimiento_aula_id' => $aula->id,
                        'establecimiento_asignatura_id' => $malla->id,
                    ],
                    [
                        'dia_semana' => $dia,
                        'hora_inicio' => $horaInicio,
                        'hora_fin' => $horaFin,
                    ],
                );

                $horariosOcupadosPorDocente[$docente->id][$dia][$bloque] = true;
                $horariosOcupadosPorAula[$aula->id][$dia][$bloque] = true;

                return;
            }
        }

        throw new \RuntimeException('No hay bloques disponibles para asignar el horario de demostración.');
    }

    /**
     * @param  list<Empleado>  $docentes
     * @param  array<int, array<int, array<int, bool>>>  $horariosOcupadosPorDocente
     * @param  array<int, array<int, array<int, bool>>>  $horariosOcupadosPorAula
     * @param  array<int, int>  $cargaPorDocente
     */
    private function seleccionarDocenteAleatorio(
        array $docentes,
        EstablecimientoAula $aula,
        array $horariosOcupadosPorDocente,
        array $horariosOcupadosPorAula,
        array &$cargaPorDocente,
    ): Empleado {
        $docentesDisponibles = [];

        foreach ($docentes as $docente) {
            for ($dia = 1; $dia <= 5; $dia++) {
                for ($bloque = 0; $bloque < 7; $bloque++) {
                    if (! ($horariosOcupadosPorDocente[$docente->id][$dia][$bloque] ?? false)
                        && ! ($horariosOcupadosPorAula[$aula->id][$dia][$bloque] ?? false)) {
                        $docentesDisponibles[] = $docente;

                        continue 3;
                    }
                }
            }
        }

        if ($docentesDisponibles === []) {
            throw new \RuntimeException('No hay docentes disponibles para asignar la clase de demostración.');
        }

        $menorCarga = min(array_map(
            fn (Empleado $docente): int => $cargaPorDocente[$docente->id] ?? 0,
            $docentesDisponibles,
        ));
        $docentesConMenorCarga = array_values(array_filter(
            $docentesDisponibles,
            fn (Empleado $docente): bool => ($cargaPorDocente[$docente->id] ?? 0) === $menorCarga,
        ));
        $docente = fake()->randomElement($docentesConMenorCarga);
        $cargaPorDocente[$docente->id] = $menorCarga + 1;

        return $docente;
    }

    /**
     * @return list<TipoInsumo>
     */
    private function crearTiposInsumos(): array
    {
        $tipos = [];

        foreach ([
            ['nombre' => 'Tareas', 'detalle' => 'Actividad individual', 'porcentaje' => '40.00'],
            ['nombre' => 'Lecciones', 'detalle' => 'Evaluación corta', 'porcentaje' => '30.00'],
            ['nombre' => 'Proyectos', 'detalle' => 'Proyecto grupal', 'porcentaje' => '30.00'],
        ] as $tipoData) {
            $tipo = TipoInsumo::query()->updateOrCreate(
                ['nombre' => $tipoData['nombre']],
                [
                    'descripcion' => 'Tipo de insumo de evaluación para datos de demostración.',
                    'tipo_calificacion' => 'cuantitativa',
                    'activo' => true,
                ],
            );

            TipoInsumoDetalle::query()->updateOrCreate(
                [
                    'tipo_insumo_id' => $tipo->id,
                    'nombre' => $tipoData['detalle'],
                ],
                [
                    'descripcion' => 'Detalle de evaluación de demostración.',
                    'valor' => null,
                    'porcentaje' => $tipoData['porcentaje'],
                    'activo' => true,
                ],
            );

            $tipos[] = $tipo;
        }

        return $tipos;
    }
}
