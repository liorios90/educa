<?php

namespace Database\Seeders;

use App\Enums\EsquemaCiclo;
use App\Enums\ModoAsistencia;
use App\Enums\ModoLibretaArea;
use App\Enums\ModoPonderacionCiclos;
use App\Enums\Role;
use App\Enums\TipoAsignatura;
use App\Enums\TipoCalificacion;
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
use App\Models\EstablecimientoPeriodo;
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
use App\Models\User;
use App\Services\OrganizacionAcademica;
use App\Services\SyncEstablecimientoEstructura;
use App\Services\SyncPeriodoEvaluacion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DemoEstablecimientoDemostracionSeeder extends Seeder
{
    public const CODIGO_AMIE = '17HDEMO00';

    public const NOMBRE = 'Demostración';

    public const PERIODO = '2026-2027';

    public const ADMIN_EMAIL = 'admin@demostracion.test';

    public const CLAVE = 'password';

    public const ALUMNOS_POR_AULA = 5;

    /**
     * @var array<string, Empleado>
     */
    private array $docentes = [];

    private int $secuenciaAlumno = 0;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            EstructuraEducativaSeeder::class,
            CurriculoEducativoSeeder::class,
            ModalidadJornadaSeeder::class,
        ]);

        DB::transaction(function (): void {
            $establecimiento = $this->establecimiento();
            $this->administrador($establecimiento);

            foreach ($this->ofertas() as $ofertaData) {
                $oferta = $this->oferta($establecimiento, $ofertaData['modalidad'], $ofertaData['jornada']);
                $nivel = Sys_Nivel::query()->where('nombre', $ofertaData['nivel'])->first();

                if ($nivel === null) {
                    throw new RuntimeException('No existe el nivel '.$ofertaData['nivel'].' en el catálogo.');
                }

                $this->configurarEstructura($establecimiento, $oferta, $nivel);

                $periodo = EstablecimientoPeriodo::query()->updateOrCreate(
                    [
                        'establecimiento_modalidad_jornada_id' => $oferta->id,
                        'nombre' => self::PERIODO,
                    ],
                    [
                        'establecimiento_id' => $establecimiento->id,
                        'fecha_inicio' => '2026-09-01',
                        'fecha_fin' => '2027-07-09',
                        'activo' => true,
                    ],
                );
                $periodo->activarEnOferta();
                $this->configurarEvaluacion($periodo);

                $tipos = $this->tiposInsumo($periodo);
                $this->aulasDocentesYMatriculas($establecimiento, $oferta, $periodo, $tipos);
            }
        });

        $this->command?->info('Demostración lista. Administrador: '.self::ADMIN_EMAIL.' / '.self::CLAVE);
    }

    private function establecimiento(): Establecimiento
    {
        $provincia = Sys_Provincia::query()->first() ?? Sys_Provincia::factory()->create();
        $circuito = Sys_Circuito::query()->with('distrito')->whereHas('distrito')->first()
            ?? Sys_Circuito::factory()->create();
        $circuito->loadMissing('distrito');

        return Establecimiento::query()->updateOrCreate(
            ['codigo_amie' => self::CODIGO_AMIE],
            [
                'nombre' => self::NOMBRE,
                'descripcion' => 'Establecimiento de demostración con Educación General Básica en la mañana y Bachillerato en la tarde.',
                'direccion' => 'Av. de la Educación y Calle del Saber',
                'telefono' => '022345678',
                'representante' => 'Ana María Villacís',
                'regimen' => 'Sierra',
                'email' => 'institucion@demostracion.test',
                'usuario' => 'demostracion',
                'activo' => 1,
                'logo' => '',
                'bachillerato_tecnico' => false,
                'bachillerato_internacional' => false,
                'zona_id' => $circuito->distrito->zona_id,
                'distrito_id' => $circuito->distrito_id,
                'circuito_id' => $circuito->id,
            ],
        );
    }

    private function administrador(Establecimiento $establecimiento): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'name' => 'Administrador '.self::NOMBRE,
                'password' => self::CLAVE,
                'establecimiento_id' => $establecimiento->id,
            ],
        );
        $user->syncRoles([Role::Admin->value]);
    }

    /**
     * @return list<array{modalidad: string, jornada: string, nivel: string}>
     */
    private function ofertas(): array
    {
        return [
            [
                'modalidad' => 'Presencial',
                'jornada' => 'Matutina',
                'nivel' => 'Educación General Básica',
            ],
            [
                'modalidad' => 'Presencial',
                'jornada' => 'Vespertina',
                'nivel' => 'Bachillerato',
            ],
        ];
    }

    private function oferta(Establecimiento $establecimiento, string $modalidadNombre, string $jornadaNombre): EstablecimientoModalidadJornada
    {
        $modalidad = Sys_Modalidad::query()->where('nombre', $modalidadNombre)->first();
        $jornada = Sys_Jornada::query()->where('nombre', $jornadaNombre)->first();

        if ($modalidad === null || $jornada === null) {
            throw new RuntimeException('Falta la modalidad o la jornada '.$modalidadNombre.' / '.$jornadaNombre.'.');
        }

        $establecimientoModalidad = EstablecimientoModalidad::query()->updateOrCreate(
            [
                'establecimiento_id' => $establecimiento->id,
                'modalidad_id' => $modalidad->id,
            ],
        );

        return EstablecimientoModalidadJornada::query()->updateOrCreate(
            [
                'establecimiento_modalidad_id' => $establecimientoModalidad->id,
                'jornada_id' => $jornada->id,
            ],
        );
    }

    private function configurarEstructura(
        Establecimiento $establecimiento,
        EstablecimientoModalidadJornada $oferta,
        Sys_Nivel $nivel,
    ): void {
        $nivel->loadMissing('subniveles.grados');
        $nombresGrados = [];
        $nombresSubniveles = [];
        $gradoIds = [];

        foreach ($nivel->subniveles as $subnivel) {
            $nombresSubniveles[$subnivel->id] = $subnivel->nombre;

            foreach ($subnivel->grados as $grado) {
                $gradoIds[] = $grado->id;
                $nombresGrados[$grado->id] = $grado->nombre;
            }
        }

        app(SyncEstablecimientoEstructura::class)->handle(
            $establecimiento,
            $oferta,
            $gradoIds,
            $nombresGrados,
            $nombresSubniveles,
        );
    }

    private function configurarEvaluacion(EstablecimientoPeriodo $periodo): void
    {
        if ($periodo->estaAbierto()) {
            return;
        }

        $esquema = EsquemaCiclo::Quimestres;
        $ciclos = array_map(fn (array $ciclo): array => [
            'nombre' => $ciclo['nombre'],
            'porcentaje' => $ciclo['porcentaje'],
            'porcentaje_insumos' => $ciclo['porcentaje_insumos'],
            'porcentaje_examen' => $ciclo['porcentaje_examen'],
            'porcentaje_proyecto' => $ciclo['porcentaje_proyecto'],
        ], $esquema->plantilla());

        app(SyncPeriodoEvaluacion::class)->handle(
            $periodo,
            $esquema,
            $esquema->nombrePorDefecto(),
            2,
            '0.00',
            '0.00',
            ModoPonderacionCiclos::Individual,
            null,
            $ciclos,
        );
    }

    /**
     * @return array<string, TipoInsumo>
     */
    private function tiposInsumo(EstablecimientoPeriodo $periodo): array
    {
        $cuantitativa = TipoInsumo::query()->updateOrCreate(
            [
                'establecimiento_periodo_id' => $periodo->id,
                'nombre' => 'Evaluación del parcial',
            ],
            [
                'descripcion' => 'Insumos cuantitativos de la demostración.',
                'tipo_calificacion' => TipoCalificacion::Cuantitativa->value,
                'activo' => true,
            ],
        );

        foreach ([
            ['Tareas', '40.00'],
            ['Lecciones', '30.00'],
            ['Proyectos', '30.00'],
        ] as [$nombre, $porcentaje]) {
            $cuantitativa->detalles()->updateOrCreate(
                ['nombre' => $nombre],
                [
                    'descripcion' => 'Detalle de evaluación de demostración.',
                    'valor' => null,
                    'porcentaje' => $porcentaje,
                    'activo' => true,
                ],
            );
        }

        $cualitativa = TipoInsumo::query()->updateOrCreate(
            [
                'establecimiento_periodo_id' => $periodo->id,
                'nombre' => 'Registro cualitativo',
            ],
            [
                'descripcion' => 'Insumo cualitativo de la demostración.',
                'tipo_calificacion' => TipoCalificacion::Cualitativa->value,
                'activo' => true,
            ],
        );
        $cualitativa->detalles()->updateOrCreate(
            ['nombre' => 'Observación'],
            [
                'descripcion' => 'Registro cualitativo de demostración.',
                'valor' => 'Cualitativa',
                'porcentaje' => null,
                'activo' => true,
            ],
        );

        return [
            TipoCalificacion::Cuantitativa->value => $cuantitativa,
            TipoCalificacion::Cualitativa->value => $cualitativa,
        ];
    }

    /**
     * @param  array<string, TipoInsumo>  $tipos
     */
    private function aulasDocentesYMatriculas(
        Establecimiento $establecimiento,
        EstablecimientoModalidadJornada $oferta,
        EstablecimientoPeriodo $periodo,
        array $tipos,
    ): void {
        $oferta->loadMissing('jornada');
        $provincia = Sys_Provincia::query()->first() ?? Sys_Provincia::factory()->create();
        $contrato = Sys_TipoContrato::query()->firstOrCreate(
            ['codigo' => 'DEMO'],
            [
                'nombre' => 'Contrato demostrativo',
                'descripcion' => 'Contrato utilizado por el conjunto de datos de demostración.',
                'usuario' => 'seeder-demo',
                'activo' => 1,
            ],
        );
        $organizacion = app(OrganizacionAcademica::class);
        $grados = EstablecimientoGrado::query()
            ->where('establecimiento_modalidad_jornada_id', $oferta->id)
            ->with(['grado.asignaturasCurriculares.area', 'subnivel.areas.asignaturas'])
            ->orderBy('grado_id')
            ->get();

        foreach ($grados as $gradoEstablecimiento) {
            $asignaturas = $this->asignaturasDelGrado($gradoEstablecimiento);

            foreach (['A', 'B'] as $paralelo) {
                $aula = EstablecimientoAula::query()->updateOrCreate(
                    [
                        'establecimiento_periodo_id' => $periodo->id,
                        'establecimiento_grado_id' => $gradoEstablecimiento->id,
                        'paralelo' => $paralelo,
                    ],
                    [
                        'establecimiento_id' => $establecimiento->id,
                        'establecimiento_modalidad_jornada_id' => $oferta->id,
                    ],
                );

                foreach ($asignaturas as $asignatura) {
                    $malla = $this->malla($establecimiento, $oferta, $gradoEstablecimiento, $asignatura);
                    $docente = $this->docente(
                        $establecimiento,
                        $provincia,
                        $contrato->id,
                        (string) $oferta->jornada?->nombre,
                        $asignatura,
                    );

                    EstablecimientoAulaDocente::query()->updateOrCreate(
                        [
                            'establecimiento_aula_id' => $aula->id,
                            'establecimiento_asignatura_id' => $malla->id,
                        ],
                        [
                            'empleado_id' => $docente->id,
                            'modo_asistencia' => ModoAsistencia::predeterminado($gradoEstablecimiento->subnivel?->nombre),
                        ],
                    );

                    $tipo = $tipos[$malla->tipo_calificacion?->value ?? TipoCalificacion::Cuantitativa->value] ?? $tipos[TipoCalificacion::Cuantitativa->value];

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

                $alumnos = [];

                for ($numero = 1; $numero <= self::ALUMNOS_POR_AULA; $numero++) {
                    $alumnos[] = $this->alumno($establecimiento, $provincia, $gradoEstablecimiento);
                }

                $organizacion->matricularVarios($aula, $alumnos, '2026-09-01');
            }
        }
    }

    /**
     * @return Collection<int, Sys_Asignatura>
     */
    private function asignaturasDelGrado(EstablecimientoGrado $gradoEstablecimiento): Collection
    {
        $grado = $gradoEstablecimiento->grado;
        $subnivel = $gradoEstablecimiento->subnivel;

        if ($grado === null || $subnivel === null) {
            return collect();
        }

        $curriculares = $grado->asignaturasCurriculares;

        if ($curriculares->count() > 1) {
            return $curriculares->values();
        }

        return $subnivel->areas
            ->flatMap(fn ($area) => $area->asignaturas)
            ->unique('id')
            ->values();
    }

    private function malla(
        Establecimiento $establecimiento,
        EstablecimientoModalidadJornada $oferta,
        EstablecimientoGrado $gradoEstablecimiento,
        Sys_Asignatura $asignatura,
    ): EstablecimientoAsignatura {
        $subnivel = $gradoEstablecimiento->subnivel;

        $malla = EstablecimientoAsignatura::query()->updateOrCreate(
            [
                'establecimiento_modalidad_jornada_id' => $oferta->id,
                'grado_id' => $gradoEstablecimiento->grado_id,
                'asignatura_id' => $asignatura->id,
            ],
            [
                'establecimiento_id' => $establecimiento->id,
                'aparece_en_libreta' => $asignatura->aparece_en_libreta,
                'horas_semanales' => $asignatura->horas_semanales,
                'tipo_calificacion' => $subnivel?->tipo_calificacion ?? TipoCalificacion::Cuantitativa,
                'tipo_asignatura' => TipoAsignatura::TroncoComun,
                'califica_por_destrezas' => (bool) $subnivel?->califica_por_destrezas,
                'usa_tabla_equivalencias' => false,
            ],
        );

        if ($asignatura->area_id !== null) {
            EstablecimientoAreaLibreta::query()->updateOrCreate(
                [
                    'establecimiento_modalidad_jornada_id' => $oferta->id,
                    'grado_id' => $gradoEstablecimiento->grado_id,
                    'area_id' => $asignatura->area_id,
                ],
                [
                    'establecimiento_id' => $establecimiento->id,
                    'modo_libreta' => ModoLibretaArea::Asignaturas,
                    'promedio_porcentual' => false,
                ],
            );
        }

        return $malla;
    }

    private function docente(
        Establecimiento $establecimiento,
        Sys_Provincia $provincia,
        int $contratoId,
        string $jornada,
        Sys_Asignatura $asignatura,
    ): Empleado {
        $clave = Str::slug($jornada.' '.$asignatura->nombre);

        if (isset($this->docentes[$clave])) {
            $this->docentes[$clave]->especialidades()->syncWithoutDetaching([$asignatura->id]);

            return $this->docentes[$clave];
        }

        $email = 'docente-'.$clave.'@demostracion.test';
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $asignatura->nombre.' '.$jornada,
                'password' => self::CLAVE,
                'establecimiento_id' => $establecimiento->id,
            ],
        );
        $user->syncRoles([Role::Docente->value]);

        $persona = Persona::query()->where('user_id', $user->id)->first()
            ?? $this->persona(
                $this->identificacionLibre('0957'),
                $asignatura->nombre,
                $jornada,
                $establecimiento,
                $provincia,
                '1988-03-12',
                1,
            );
        $persona->update(['user_id' => $user->id]);

        $docente = Empleado::query()->updateOrCreate(
            ['persona_id' => $persona->id],
            [
                'tipo_contrato_id' => $contratoId,
                'cargo_id' => null,
                'funcion_id' => null,
                'horas' => 40,
                'anios_experiencia' => 5,
                'anios_instituto' => 2,
                'contacto_emergencia' => 'Secretaría '.self::NOMBRE,
                'contacto_num' => '0991000000',
                'usuario' => 'docente-'.$clave,
                'activo' => 1,
            ],
        );
        $docente->especialidades()->syncWithoutDetaching([$asignatura->id]);
        $this->docentes[$clave] = $docente;

        return $docente;
    }

    private function alumno(
        Establecimiento $establecimiento,
        Sys_Provincia $provincia,
        EstablecimientoGrado $gradoEstablecimiento,
    ): Alumno {
        $this->secuenciaAlumno++;
        $identificacion = sprintf('0955%06d', $this->secuenciaAlumno);
        $identificacionPadre = sprintf('0956%06d', $this->secuenciaAlumno);
        $grado = $gradoEstablecimiento->grado?->nombre ?? $gradoEstablecimiento->nombre ?? 'Grado';
        $nombres = $this->nombrePersona($this->secuenciaAlumno);
        $persona = $this->persona(
            $identificacion,
            $nombres['nombres'],
            $nombres['apellidos'],
            $establecimiento,
            $provincia,
            $this->fechaNacimiento($grado),
            $this->secuenciaAlumno % 2 === 0 ? 1 : 2,
        );
        $usuarioAlumno = User::query()->updateOrCreate(
            ['email' => 'alumno-'.$identificacion.'@demostracion.test'],
            [
                'name' => $nombres['apellidos'].' '.$nombres['nombres'],
                'password' => self::CLAVE,
                'establecimiento_id' => $establecimiento->id,
            ],
        );
        $usuarioAlumno->syncRoles([Role::Alumno->value]);
        $persona->update(['user_id' => $usuarioAlumno->id]);

        $nombresPadre = $this->nombrePersona($this->secuenciaAlumno + 40);
        $personaPadre = $this->persona(
            $identificacionPadre,
            $nombresPadre['nombres'],
            $nombres['apellidos'],
            $establecimiento,
            $provincia,
            sprintf('%d-04-18', 1978 + ($this->secuenciaAlumno % 12)),
            $this->secuenciaAlumno % 2 === 0 ? 2 : 1,
        );
        $usuarioPadre = User::query()->updateOrCreate(
            ['email' => 'padre-'.$identificacionPadre.'@demostracion.test'],
            [
                'name' => $nombres['apellidos'].' '.$nombresPadre['nombres'],
                'password' => self::CLAVE,
                'establecimiento_id' => $establecimiento->id,
            ],
        );
        $usuarioPadre->syncRoles([Role::Padre->value]);
        $personaPadre->update(['user_id' => $usuarioPadre->id]);

        $padre = Padre::query()->updateOrCreate(
            ['persona_id' => $personaPadre->id],
            [
                'estado_civil_id' => 'casado',
                'vive_con_estudiante' => 1,
                'titulo' => 'Representante',
                'usuario' => 'padre-'.$identificacionPadre,
                'activo' => 1,
            ],
        );

        return Alumno::query()->updateOrCreate(
            ['persona_id' => $persona->id],
            [
                'padre_id' => $padre->id,
                'contacto_emergencia' => $nombresPadre['nombres'].' '.$nombres['apellidos'],
                'usuario' => 'alumno-'.$identificacion,
                'id_estructura_form_matricula' => 0,
                'activo' => 1,
            ],
        );
    }

    /**
     * @return array{nombres: string, apellidos: string}
     */
    private function nombrePersona(int $secuencia): array
    {
        $nombres = [
            'Mateo', 'Sofía', 'Santiago', 'Valentina', 'Sebastián', 'Isabella', 'Nicolás', 'Camila',
            'Diego', 'Lucía', 'Andrés', 'Martina', 'Joaquín', 'Emma', 'Benjamín', 'Mía',
            'Gabriel', 'Antonella', 'Emilio', 'Renata', 'Thiago', 'Amanda', 'Daniel', 'Paula',
        ];
        $apellidos = [
            'García', 'Rodríguez', 'Martínez', 'López', 'González', 'Villacís', 'Quishpe', 'Caiza',
            'Maldonado', 'Paredes', 'Cevallos', 'Andrade', 'Zambrano', 'Navarrete', 'Chávez', 'Molina',
            'Salazar', 'Cárdenas', 'Espinoza', 'Rivadeneira',
        ];

        return [
            'nombres' => $nombres[$secuencia % count($nombres)],
            'apellidos' => $apellidos[$secuencia % count($apellidos)].' '.$apellidos[($secuencia + 7) % count($apellidos)],
        ];
    }

    private function fechaNacimiento(string $grado): string
    {
        $anio = match ($grado) {
            'Primero de EGB' => 2020,
            'Segundo de EGB' => 2019,
            'Tercero de EGB' => 2018,
            'Cuarto de EGB' => 2017,
            'Quinto de EGB' => 2016,
            'Sexto de EGB' => 2015,
            'Séptimo de EGB' => 2014,
            'Octavo de EGB' => 2013,
            'Noveno de EGB' => 2012,
            'Décimo de EGB' => 2011,
            'Primero de Bachillerato' => 2010,
            'Segundo de Bachillerato' => 2009,
            'Tercero de Bachillerato' => 2008,
            default => 2012,
        };

        return $anio.'-05-15';
    }

    private function identificacionLibre(string $prefijo): string
    {
        for ($numero = 1; $numero <= 999999; $numero++) {
            $identificacion = $prefijo.str_pad((string) $numero, 6, '0', STR_PAD_LEFT);

            if (! Persona::query()->where('identificacion', $identificacion)->exists()) {
                return $identificacion;
            }
        }

        throw new RuntimeException('No hay identificaciones disponibles para los docentes de demostración.');
    }

    private function persona(
        string $identificacion,
        string $nombres,
        string $apellidos,
        Establecimiento $establecimiento,
        Sys_Provincia $provincia,
        string $fechaNacimiento,
        int $generoId,
    ): Persona {
        return Persona::query()->updateOrCreate(
            ['identificacion' => $identificacion],
            [
                'tipo_identificacion_id' => 1,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'genero_id' => $generoId,
                'fecha_nacimiento' => $fechaNacimiento,
                'ciudad_nacimiento' => $provincia->nombre,
                'provincia_id' => $provincia->id,
                'parroquia' => 'Centro',
                'direccion' => 'Dirección de demostración',
                'telefono1' => '022345678',
                'telefono2' => '0990000000',
                'nacionalidad_id' => $provincia->pais_id,
                'usuario' => $identificacion,
                'activo' => 1,
                'establecimiento_id' => $establecimiento->id,
            ],
        );
    }
}
