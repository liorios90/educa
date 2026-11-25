<?php

use App\Enums\Role;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\Notificacion;
use App\Models\NotificacionArchivo;
use App\Models\NotificacionDestinatario;
use App\Models\Persona;
use App\Models\User;
use Database\Seeders\NavigationSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @return array{
 *     establecimiento: Establecimiento,
 *     docente: User,
 *     cuentaAlumno: User,
 *     padre: User,
 *     cuentaCompanero: User,
 *     padreCompanero: User,
 *     alumnoAulaB: User,
 *     colega: User,
 *     admin: User,
 *     docenteAjeno: User,
 *     secretaria: User,
 *     aula: EstablecimientoAula
 * }
 */
function escenarioNotificaciones(): array
{
    $escenario = escenarioLibreta();
    $docente = assignRole(User::factory()->create([
        'name' => 'Ana Perez',
        'establecimiento_id' => $escenario['establecimiento']->id,
    ]), Role::Docente);
    $escenario['empleado']->persona->update([
        'user_id' => $docente->id,
        'apellidos' => 'Perez',
        'nombres' => 'Ana',
    ]);

    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Herrera');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    $cuentaAlumno = cuentaAlumnoDe($alumno, $escenario['establecimiento']);
    $alumno->persona->update(['apellidos' => 'Herrera', 'nombres' => 'Ana']);

    $padre = cuentaPadreDe($escenario['establecimiento'], $alumno);
    $padre->persona->update(['apellidos' => 'Herrera', 'nombres' => 'Carlos']);

    $companero = alumnoDelEstablecimiento($escenario['establecimiento'], 'Castro');
    matricularAlumnoLibreta($escenario['aula'], $companero);
    $cuentaCompanero = cuentaAlumnoDe($companero, $escenario['establecimiento']);
    $companero->persona->update(['apellidos' => 'Castro', 'nombres' => 'Ana']);
    $padreCompanero = cuentaPadreDe($escenario['establecimiento'], $companero);
    $padreCompanero->persona->update(['apellidos' => 'Castro', 'nombres' => 'Luis']);

    $alumnoB = alumnoDelEstablecimiento($escenario['establecimiento'], 'Ortiz');
    matricularAlumnoLibreta($escenario['aulaB'], $alumnoB);
    $alumnoAulaB = cuentaAlumnoDe($alumnoB, $escenario['establecimiento']);
    $alumnoB->persona->update(['apellidos' => 'Ortiz', 'nombres' => 'Eva']);

    $colega = assignRole(User::factory()->create([
        'name' => 'Bruno Colega',
        'establecimiento_id' => $escenario['establecimiento']->id,
    ]), Role::Docente);
    $personaColega = Persona::factory()->create([
        'user_id' => $colega->id,
        'establecimiento_id' => $escenario['establecimiento']->id,
        'apellidos' => 'Colega',
        'nombres' => 'Bruno',
    ]);
    $empleadoColega = Empleado::factory()->for($personaColega)->create();
    EstablecimientoAulaDocente::factory()->create([
        'establecimiento_aula_id' => $escenario['aulaB']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'empleado_id' => $empleadoColega->id,
    ]);

    $admin = adminOf($escenario['establecimiento']);

    $ajeno = escenarioLibreta(['grado' => '9no Básica']);
    $docenteAjeno = assignRole(User::factory()->create([
        'name' => 'Docente Ajeno',
        'establecimiento_id' => $ajeno['establecimiento']->id,
    ]), Role::Docente);
    $ajeno['empleado']->persona->update([
        'user_id' => $docenteAjeno->id,
        'apellidos' => 'Ajeno',
        'nombres' => 'Marta',
    ]);

    return [
        'establecimiento' => $escenario['establecimiento'],
        'docente' => $docente,
        'cuentaAlumno' => $cuentaAlumno,
        'padre' => $padre,
        'cuentaCompanero' => $cuentaCompanero,
        'padreCompanero' => $padreCompanero,
        'alumnoAulaB' => $alumnoAulaB,
        'colega' => $colega,
        'admin' => $admin,
        'docenteAjeno' => $docenteAjeno,
        'secretaria' => $escenario['secretaria'],
        'aula' => $escenario['aula'],
    ];
}

it('redirects guests from the notification inbox to the login page', function () {
    $this->get(route('notificaciones.index'))
        ->assertRedirect(route('login'));
});

it('lets every signed-in user open the notification inbox', function (User $user) {
    $this->actingAs($user)
        ->get(route('notificaciones.index'))
        ->assertOk()
        ->assertSee('Notificaciones')
        ->assertSee('No tienes notificaciones recibidas.');
})->with([
    'systems' => fn () => assignRole(User::factory()->create(), Role::Sistemas),
    'secretary' => fn () => secretariaOf(Establecimiento::factory()->create()),
]);

it('lets a student write only to the teachers of their classes', function () {
    $escenario = escenarioNotificaciones();

    $this->actingAs($escenario['cuentaAlumno'])
        ->get(route('notificaciones.create'))
        ->assertOk()
        ->assertSee('Perez Ana (Docente de 8vo Básica A)')
        ->assertDontSee('Colega Bruno')
        ->assertDontSee('Castro Ana')
        ->assertDontSee('Herrera Carlos')
        ->assertDontSee('Ajeno Marta');
});

it('lets a student send a notification to their teacher', function () {
    $escenario = escenarioNotificaciones();

    $this->actingAs($escenario['cuentaAlumno'])
        ->post(route('notificaciones.store'), [
            'destinatarios' => [$escenario['docente']->id],
            'asunto' => 'Consulta de tarea',
            'cuerpo' => '¿Puedo entregar mañana?',
        ])
        ->assertRedirect();

    $notificacion = Notificacion::query()->first();

    expect($notificacion)
        ->asunto->toBe('Consulta de tarea')
        ->cuerpo->toBe('¿Puedo entregar mañana?')
        ->remitente_id->toBe($escenario['cuentaAlumno']->id)
        ->establecimiento_id->toBe($escenario['establecimiento']->id);

    $this->assertDatabaseHas('notificacion_destinatarios', [
        'notificacion_id' => $notificacion->id,
        'user_id' => $escenario['docente']->id,
        'leida_at' => null,
    ]);
});

it('does not let a student send a notification to a classmate', function () {
    $escenario = escenarioNotificaciones();

    $this->actingAs($escenario['cuentaAlumno'])
        ->from(route('notificaciones.create'))
        ->post(route('notificaciones.store'), [
            'destinatarios' => [$escenario['cuentaCompanero']->id],
            'asunto' => 'Hola',
            'cuerpo' => '¿Trajiste el cuaderno?',
        ])
        ->assertRedirect(route('notificaciones.create'))
        ->assertSessionHasErrors('destinatarios');

    $this->assertDatabaseMissing('notificaciones', ['asunto' => 'Hola']);
});

it('lets a parent write only to the teachers of their children', function () {
    $escenario = escenarioNotificaciones();

    $this->actingAs($escenario['padre'])
        ->get(route('notificaciones.create'))
        ->assertOk()
        ->assertSee('Perez Ana (Docente de 8vo Básica A)')
        ->assertDontSee('Colega Bruno')
        ->assertDontSee('Castro Luis')
        ->assertDontSee('Ajeno Marta');
});

it('lets a parent send a notification to a teacher of their child', function () {
    $escenario = escenarioNotificaciones();

    $this->actingAs($escenario['padre'])
        ->post(route('notificaciones.store'), [
            'destinatarios' => [$escenario['docente']->id],
            'asunto' => 'Reunión',
            'cuerpo' => '¿Podemos conversar el viernes?',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('notificaciones', [
        'remitente_id' => $escenario['padre']->id,
        'asunto' => 'Reunión',
    ]);
});

it('lets a teacher write to school staff and to parents and students of an assigned classroom', function () {
    $escenario = escenarioNotificaciones();
    assignRole(User::factory()->create([
        'name' => 'Rojas Elena',
        'establecimiento_id' => $escenario['establecimiento']->id,
    ]), Role::Empleado);
    User::query()
        ->where('establecimiento_id', $escenario['docenteAjeno']->establecimiento_id)
        ->where('name', 'Secretaria Andina')
        ->update(['name' => 'Secretaria Ajena']);

    $this->actingAs($escenario['docente'])
        ->get(route('notificaciones.create'))
        ->assertOk()
        ->assertSee('Buscar y seleccionar varios')
        ->assertSee('Enviar a')
        ->assertSee('Elige uno o varios roles. La lista solo muestra a las personas de esos roles.')
        ->assertSee('Puedes marcar varios. Dentro de los roles elegidos, busca por nombre, aula o alumno.')
        ->assertSee('name="roles[]"', false)
        ->assertSee('rolActivo(opcion)', false)
        ->assertSee('agregarTodos', false)
        ->assertSee('Todos los padres')
        ->assertSee('Todos los alumnos')
        ->assertSee('Elige el aula para ver a sus padres y alumnos. Puedes añadirlos a todos.')
        ->assertSee('Selecciona un aula para ver padres y alumnos.')
        ->assertSee('\u0022roles\u0022:[\u0022Alumno\u0022]', false)
        ->assertSee('\u0022roles\u0022:[\u0022Padre\u0022]', false)
        ->assertSee('\u0022roles\u0022:[\u0022Admin\u0022]', false)
        ->assertSee('\u0022roles\u0022:[\u0022Secretaria\u0022]', false)
        ->assertSee('\u0022roles\u0022:[\u0022Empleado\u0022]', false)
        ->assertSee('\u0022aulas\u0022:['.$escenario['aula']->id.']', false)
        ->assertSee('8vo Básica A')
        ->assertSee('Selecciona al menos un rol para ver destinatarios.')
        ->assertSee('Herrera Ana (Alumno)')
        ->assertSee('Herrera Carlos (Padre de Herrera Ana)')
        ->assertSee('Castro Ana (Alumno)')
        ->assertSee('Castro Luis (Padre de Castro Ana)')
        ->assertSee('Secretaria Andina (Secretaría)')
        ->assertSee('Director Andino (Administrador)')
        ->assertSee('Rojas Elena (Empleado)')
        ->assertSee('font-semibold text-slate-800', false)
        ->assertSee('x-text="opcion.nombre"', false)
        ->assertDontSee('Colega Bruno')
        ->assertDontSee('Ortiz Eva')
        ->assertDontSee('Secretaria Ajena')
        ->assertDontSee('Ajeno Marta')
        ->assertDontSee('8vo Básica B');
});

it('shows every assigned role next to each recipient name', function () {
    $escenario = escenarioNotificaciones();
    assignRole($escenario['secretaria'], Role::Empleado);

    $this->actingAs($escenario['docente'])
        ->get(route('notificaciones.create'))
        ->assertOk()
        ->assertSee('Secretaria Andina (Secretaría, Empleado)');
});

it('lists every child next to a parent recipient', function () {
    $escenario = escenarioNotificaciones();
    $hijo = Alumno::query()
        ->whereHas('persona', fn ($query) => $query->where('user_id', $escenario['cuentaAlumno']->id))
        ->first();
    $otro = alumnoDelEstablecimiento($escenario['establecimiento'], 'Herrera');
    $otro->persona->update(['nombres' => 'Luis']);
    $otro->update(['padre_id' => $hijo->padre_id]);

    $this->actingAs($escenario['docente'])
        ->get(route('notificaciones.create'))
        ->assertOk()
        ->assertSee('Herrera Carlos (Padre de Herrera Ana, Herrera Luis)');
});

it('lets a teacher send a notification to a student, a parent and school staff', function () {
    $escenario = escenarioNotificaciones();
    $empleado = assignRole(User::factory()->create([
        'name' => 'Rojas Elena',
        'establecimiento_id' => $escenario['establecimiento']->id,
    ]), Role::Empleado);

    $this->actingAs($escenario['docente'])
        ->post(route('notificaciones.store'), [
            'destinatarios' => [
                $escenario['cuentaAlumno']->id,
                $escenario['padre']->id,
                $escenario['secretaria']->id,
                $escenario['admin']->id,
                $empleado->id,
            ],
            'asunto' => 'Recordatorio',
            'cuerpo' => 'La prueba es el lunes.',
        ])
        ->assertRedirect();

    $notificacion = Notificacion::query()->where('asunto', 'Recordatorio')->first();

    expect(NotificacionDestinatario::query()->where('notificacion_id', $notificacion->id)->pluck('user_id')->sort()->values()->all())
        ->toBe(collect([
            $escenario['cuentaAlumno']->id,
            $escenario['padre']->id,
            $escenario['secretaria']->id,
            $escenario['admin']->id,
            $empleado->id,
        ])->sort()->values()->all());
});

it('does not let a teacher send a notification to another teacher', function () {
    $escenario = escenarioNotificaciones();

    $this->actingAs($escenario['docente'])
        ->from(route('notificaciones.create'))
        ->post(route('notificaciones.store'), [
            'destinatarios' => [$escenario['colega']->id],
            'asunto' => 'Aviso',
            'cuerpo' => 'Este mensaje no debe llegar.',
        ])
        ->assertRedirect(route('notificaciones.create'))
        ->assertSessionHasErrors('destinatarios');

    $this->assertDatabaseMissing('notificaciones', ['asunto' => 'Aviso']);
});

it('does not let a teacher send a notification to a student from another classroom', function () {
    $escenario = escenarioNotificaciones();

    $this->actingAs($escenario['docente'])
        ->from(route('notificaciones.create'))
        ->post(route('notificaciones.store'), [
            'destinatarios' => [$escenario['alumnoAulaB']->id],
            'asunto' => 'Aviso',
            'cuerpo' => 'Este mensaje no debe llegar.',
        ])
        ->assertRedirect(route('notificaciones.create'))
        ->assertSessionHasErrors('destinatarios');

    $this->assertDatabaseMissing('notificaciones', ['asunto' => 'Aviso']);
});

it('lets an administrator write to teachers and parents but not students', function () {
    $escenario = escenarioNotificaciones();

    $this->actingAs($escenario['admin'])
        ->get(route('notificaciones.create'))
        ->assertOk()
        ->assertSee('Perez Ana (Docente de 8vo Básica A)')
        ->assertSee('Colega Bruno (Docente de 8vo Básica B)')
        ->assertSee('Herrera Carlos (Padre de Herrera Ana)')
        ->assertSee('Castro Luis (Padre de Castro Ana)')
        ->assertDontSee('Herrera Ana (Alumno)')
        ->assertDontSee('Ortiz Eva');
});

it('lets an administrator send a notification to a teacher and a parent', function () {
    $escenario = escenarioNotificaciones();

    $this->actingAs($escenario['admin'])
        ->post(route('notificaciones.store'), [
            'destinatarios' => [$escenario['docente']->id, $escenario['padre']->id],
            'asunto' => 'Circular',
            'cuerpo' => 'Reunión de representantes el martes.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('notificaciones', [
        'remitente_id' => $escenario['admin']->id,
        'asunto' => 'Circular',
    ]);
});

it('does not let an administrator send a notification to a student', function () {
    $escenario = escenarioNotificaciones();

    $this->actingAs($escenario['admin'])
        ->from(route('notificaciones.create'))
        ->post(route('notificaciones.store'), [
            'destinatarios' => [$escenario['cuentaAlumno']->id],
            'asunto' => 'Aviso',
            'cuerpo' => 'Este mensaje no debe llegar.',
        ])
        ->assertRedirect(route('notificaciones.create'))
        ->assertSessionHasErrors('destinatarios');

    $this->assertDatabaseMissing('notificaciones', ['asunto' => 'Aviso']);
});

it('shows a received notification in the inbox and marks it as read when opened', function () {
    $escenario = escenarioNotificaciones();
    $notificacion = Notificacion::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'remitente_id' => $escenario['docente']->id,
        'asunto' => 'Tarea de matemática',
        'cuerpo' => 'Resuelve las páginas 12 y 13.',
    ]);
    NotificacionDestinatario::factory()->create([
        'notificacion_id' => $notificacion->id,
        'user_id' => $escenario['cuentaAlumno']->id,
    ]);

    $this->actingAs($escenario['cuentaAlumno'])
        ->get(route('notificaciones.index'))
        ->assertSee('Tarea de matemática')
        ->assertSee('De: Perez Ana (Docente)');

    $this->actingAs($escenario['cuentaAlumno'])
        ->get(route('notificaciones.show', $notificacion))
        ->assertSee('Tarea de matemática')
        ->assertSee('Resuelve las páginas 12 y 13.')
        ->assertSee('Perez Ana (Docente)')
        ->assertSee('Herrera Ana (Alumno)')
        ->assertSee('Responder')
        ->assertSee('Responder a Perez Ana')
        ->assertSee('Enviar respuesta')
        ->assertSee('open-modal', false)
        ->assertSee('notificacion-responder', false);

    expect(NotificacionDestinatario::query()->where('notificacion_id', $notificacion->id)->value('leida_at'))
        ->not->toBeNull();
});

it('shows a sent notification in the sent folder', function () {
    $escenario = escenarioNotificaciones();
    $notificacion = Notificacion::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'remitente_id' => $escenario['docente']->id,
        'asunto' => 'Enviado al aula',
        'cuerpo' => 'Traer el libro.',
    ]);
    NotificacionDestinatario::factory()->create([
        'notificacion_id' => $notificacion->id,
        'user_id' => $escenario['cuentaAlumno']->id,
    ]);

    $this->actingAs($escenario['docente'])
        ->get(route('notificaciones.index', ['bandeja' => 'enviadas']))
        ->assertSee('Enviado al aula')
        ->assertSee('Para: Herrera Ana (Alumno)');
});

it('does not show a notification from another school', function () {
    $escenario = escenarioNotificaciones();
    $ajena = Notificacion::factory()->create([
        'establecimiento_id' => Establecimiento::factory(),
        'remitente_id' => $escenario['docenteAjeno']->id,
        'asunto' => 'Mensaje de otro colegio',
        'cuerpo' => 'No deberías ver esto.',
    ]);
    NotificacionDestinatario::factory()->create([
        'notificacion_id' => $ajena->id,
        'user_id' => $escenario['docenteAjeno']->id,
    ]);

    $this->actingAs($escenario['cuentaAlumno'])
        ->get(route('notificaciones.show', $ajena))
        ->assertNotFound();
});

it('explains when the compose form is missing required fields', function () {
    $escenario = escenarioNotificaciones();

    $this->actingAs($escenario['docente'])
        ->from(route('notificaciones.create'))
        ->post(route('notificaciones.store'), [])
        ->assertRedirect(route('notificaciones.create'))
        ->assertSessionHasErrors([
            'destinatarios' => 'Selecciona al menos un destinatario.',
            'asunto' => 'Escribe el asunto.',
            'cuerpo' => 'Escribe el mensaje.',
        ]);
});

it('escapes a dangerous subject and body on the notification', function () {
    $escenario = escenarioNotificaciones();
    $notificacion = Notificacion::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'remitente_id' => $escenario['docente']->id,
        'asunto' => '<script>alert(1)</script>',
        'cuerpo' => '<script>alert(2)</script>',
    ]);
    NotificacionDestinatario::factory()->create([
        'notificacion_id' => $notificacion->id,
        'user_id' => $escenario['cuentaAlumno']->id,
    ]);

    $this->actingAs($escenario['cuentaAlumno'])
        ->get(route('notificaciones.show', $notificacion))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<script>alert(2)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertSee('&lt;script&gt;alert(2)&lt;/script&gt;', false);
});

it('lets a recipient reply to the sender even when they cannot compose to that person', function () {
    $escenario = escenarioNotificaciones();
    $original = Notificacion::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'remitente_id' => $escenario['colega']->id,
        'asunto' => 'Circular institucional',
        'cuerpo' => 'Reunión el martes.',
    ]);
    NotificacionDestinatario::factory()->create([
        'notificacion_id' => $original->id,
        'user_id' => $escenario['docente']->id,
    ]);

    $this->actingAs($escenario['docente'])
        ->from(route('notificaciones.create'))
        ->post(route('notificaciones.store'), [
            'destinatarios' => [$escenario['colega']->id],
            'asunto' => 'No permitido',
            'cuerpo' => 'No debería enviarse.',
        ])
        ->assertSessionHasErrors('destinatarios');

    $this->actingAs($escenario['docente'])
        ->post(route('notificaciones.responder', $original), [
            'cuerpo' => 'Confirmo mi asistencia.',
        ])
        ->assertRedirect();

    $respuesta = Notificacion::query()->where('asunto', 'Re: Circular institucional')->first();

    expect($respuesta)
        ->remitente_id->toBe($escenario['docente']->id)
        ->cuerpo->toBe('Confirmo mi asistencia.');

    $this->assertDatabaseHas('notificacion_destinatarios', [
        'notificacion_id' => $respuesta->id,
        'user_id' => $escenario['colega']->id,
    ]);
});

it('does not show a reply form on a sent notification', function () {
    $escenario = escenarioNotificaciones();
    $notificacion = Notificacion::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'remitente_id' => $escenario['docente']->id,
        'asunto' => 'Enviado al aula',
        'cuerpo' => 'Traer el libro.',
    ]);
    NotificacionDestinatario::factory()->create([
        'notificacion_id' => $notificacion->id,
        'user_id' => $escenario['cuentaAlumno']->id,
    ]);

    $this->actingAs($escenario['docente'])
        ->get(route('notificaciones.show', $notificacion))
        ->assertSee('Enviado al aula')
        ->assertDontSee('Enviar respuesta');
});

it('does not let a stranger reply to a notification', function () {
    $escenario = escenarioNotificaciones();
    $notificacion = Notificacion::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'remitente_id' => $escenario['docente']->id,
        'asunto' => 'Privada',
        'cuerpo' => 'Solo para un alumno.',
    ]);
    NotificacionDestinatario::factory()->create([
        'notificacion_id' => $notificacion->id,
        'user_id' => $escenario['cuentaAlumno']->id,
    ]);

    $this->actingAs($escenario['cuentaCompanero'])
        ->post(route('notificaciones.responder', $notificacion), [
            'cuerpo' => 'No debería llegar.',
        ])
        ->assertNotFound();

    $this->assertDatabaseMissing('notificaciones', ['cuerpo' => 'No debería llegar.']);
});

it('explains when the reply is empty', function () {
    $escenario = escenarioNotificaciones();
    $notificacion = Notificacion::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'remitente_id' => $escenario['docente']->id,
        'asunto' => 'Consulta',
        'cuerpo' => '¿Confirmas?',
    ]);
    NotificacionDestinatario::factory()->create([
        'notificacion_id' => $notificacion->id,
        'user_id' => $escenario['cuentaAlumno']->id,
    ]);

    $this->actingAs($escenario['cuentaAlumno'])
        ->from(route('notificaciones.show', $notificacion))
        ->post(route('notificaciones.responder', $notificacion), [])
        ->assertRedirect(route('notificaciones.show', $notificacion))
        ->assertSessionHasErrors(['cuerpo' => 'Escribe la respuesta.']);
});

it('shows the notifications icon in the top bar with unread count', function () {
    $escenario = escenarioNotificaciones();
    $notificacion = Notificacion::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'remitente_id' => $escenario['docente']->id,
        'asunto' => 'Pendiente',
        'cuerpo' => 'Revisa esto.',
    ]);
    NotificacionDestinatario::factory()->create([
        'notificacion_id' => $notificacion->id,
        'user_id' => $escenario['cuentaAlumno']->id,
    ]);

    $this->actingAs($escenario['cuentaAlumno'])
        ->get(route('dashboard'))
        ->assertSee('Notificaciones')
        ->assertSee(route('notificaciones.index'), false)
        ->assertSee('1 sin leer', false);
});

it('lets any sender attach files and lets the recipient open them', function () {
    Storage::fake('local');
    $escenario = escenarioNotificaciones();

    $this->actingAs($escenario['cuentaAlumno'])
        ->get(route('notificaciones.create'))
        ->assertSee('Archivos adjuntos')
        ->assertSee('name="archivos[]"', false);

    $this->actingAs($escenario['cuentaAlumno'])
        ->post(route('notificaciones.store'), [
            'destinatarios' => [$escenario['docente']->id],
            'asunto' => 'Comprobante',
            'cuerpo' => 'Adjunto la foto y el documento.',
            'archivos' => [
                UploadedFile::fake()->image('foto.png'),
                UploadedFile::fake()->create('aviso.pdf', 40, 'application/pdf'),
            ],
        ])
        ->assertRedirect(route('notificaciones.show', Notificacion::query()->where('asunto', 'Comprobante')->firstOrFail()));

    $notificacion = Notificacion::query()->where('asunto', 'Comprobante')->firstOrFail();
    $imagen = $notificacion->archivos()->where('nombre_original', 'foto.png')->firstOrFail();
    $pdf = $notificacion->archivos()->where('nombre_original', 'aviso.pdf')->firstOrFail();
    $imagen->update(['nombre_original' => '<script>alert(1)</script>.png']);

    Storage::disk('local')->assertExists($imagen->path);
    Storage::disk('local')->assertExists($pdf->path);

    $this->actingAs($escenario['docente'])
        ->get(route('notificaciones.index'))
        ->assertSee('Comprobante')
        ->assertSee('Incluye archivos adjuntos');

    $this->actingAs($escenario['docente'])
        ->get(route('notificaciones.show', $notificacion))
        ->assertSee('Archivos adjuntos')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;.png', false)
        ->assertDontSee('<script>alert(1)</script>.png', false)
        ->assertSee('aviso.pdf')
        ->assertSee(route('notificaciones.archivos.show', [$notificacion, $imagen]), false)
        ->assertSee(route('notificaciones.archivos.show', [$notificacion, $pdf]), false);

    $imagenRespuesta = $this->actingAs($escenario['docente'])
        ->get(route('notificaciones.archivos.show', [$notificacion, $imagen]));

    $imagenRespuesta->assertOk();
    expect($imagenRespuesta->headers->get('content-type'))->toBe('image/png')
        ->and($imagenRespuesta->headers->get('content-disposition'))->toContain('inline')
        ->and($imagenRespuesta->streamedContent())->not->toBe('');

    $pdfRespuesta = $this->actingAs($escenario['docente'])
        ->get(route('notificaciones.archivos.show', [$notificacion, $pdf]));

    $pdfRespuesta->assertOk();
    expect($pdfRespuesta->headers->get('content-type'))->toBe('application/pdf')
        ->and($pdfRespuesta->headers->get('content-disposition'))->toContain('inline');

    $this->actingAs($escenario['cuentaCompanero'])
        ->get(route('notificaciones.archivos.show', [$notificacion, $imagen]))
        ->assertNotFound();
});

it('lets a recipient reply with an attachment the sender can download', function () {
    Storage::fake('local');
    $escenario = escenarioNotificaciones();
    $original = Notificacion::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'remitente_id' => $escenario['docente']->id,
        'asunto' => 'Circular',
        'cuerpo' => 'Revisa el comunicado.',
    ]);
    NotificacionDestinatario::factory()->create([
        'notificacion_id' => $original->id,
        'user_id' => $escenario['padre']->id,
    ]);

    $this->actingAs($escenario['padre'])
        ->post(route('notificaciones.responder', $original), [
            'cuerpo' => 'Adjunto la autorización.',
            'archivos' => [
                UploadedFile::fake()->createWithContent('autorizacion.txt', 'autorizado'),
            ],
        ])
        ->assertRedirect();

    $respuesta = Notificacion::query()->where('asunto', 'Re: Circular')->firstOrFail();
    $archivo = $respuesta->archivos()->firstOrFail();

    $this->actingAs($escenario['docente'])
        ->get(route('notificaciones.show', $respuesta))
        ->assertSee('Descargar autorizacion.txt');

    $descarga = $this->actingAs($escenario['docente'])
        ->get(route('notificaciones.archivos.show', [$respuesta, $archivo]));

    $descarga->assertOk();
    expect($descarga->headers->get('content-disposition'))->toContain('attachment')
        ->and($descarga->streamedContent())->toBe('autorizado');
});

it('does not store a notification when an attachment has a forbidden format', function () {
    Storage::fake('local');
    $escenario = escenarioNotificaciones();

    $this->actingAs($escenario['docente'])
        ->from(route('notificaciones.create'))
        ->post(route('notificaciones.store'), [
            'destinatarios' => [$escenario['cuentaAlumno']->id],
            'asunto' => 'Archivo inválido',
            'cuerpo' => 'No debería guardarse.',
            'archivos' => [
                UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
            ],
        ])
        ->assertRedirect(route('notificaciones.create'))
        ->assertSessionHasErrors([
            'archivos.0' => 'El archivo no tiene un formato permitido. Usa PDF, Word, Excel, PowerPoint, imagen o texto.',
        ]);

    $this->assertDatabaseMissing('notificaciones', ['asunto' => 'Archivo inválido']);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

it('does not let a recipient open an attachment from another notification', function () {
    Storage::fake('local');
    $escenario = escenarioNotificaciones();
    $propia = Notificacion::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'remitente_id' => $escenario['docente']->id,
        'asunto' => 'Para el alumno',
        'cuerpo' => 'Mensaje.',
    ]);
    NotificacionDestinatario::factory()->create([
        'notificacion_id' => $propia->id,
        'user_id' => $escenario['cuentaAlumno']->id,
    ]);
    $ajena = Notificacion::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'remitente_id' => $escenario['docente']->id,
        'asunto' => 'Para otro',
        'cuerpo' => 'Otro mensaje.',
    ]);
    NotificacionDestinatario::factory()->create([
        'notificacion_id' => $ajena->id,
        'user_id' => $escenario['cuentaCompanero']->id,
    ]);
    Storage::disk('local')->put('notificaciones/'.$ajena->id.'/secreto.pdf', 'secreto');
    $archivo = NotificacionArchivo::factory()->create([
        'notificacion_id' => $ajena->id,
        'path' => 'notificaciones/'.$ajena->id.'/secreto.pdf',
        'nombre_original' => 'secreto.pdf',
        'mime' => 'application/pdf',
    ]);

    $this->actingAs($escenario['cuentaAlumno'])
        ->get(route('notificaciones.archivos.show', [$propia, $archivo]))
        ->assertNotFound();
});

it('shows the notifications link to every role', function () {
    $this->seed(NavigationSeeder::class);
    $establecimiento = Establecimiento::factory()->create();

    foreach ([
        adminOf($establecimiento),
        secretariaOf($establecimiento),
        assignRole(User::factory()->create(['establecimiento_id' => $establecimiento->id]), Role::Docente),
        assignRole(User::factory()->create(['establecimiento_id' => $establecimiento->id]), Role::Padre),
        assignRole(User::factory()->create(['establecimiento_id' => $establecimiento->id]), Role::Alumno),
        assignRole(User::factory()->create(), Role::Sistemas),
    ] as $user) {
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee('Notificaciones')
            ->assertSee(route('notificaciones.index'), false);
    }
});
