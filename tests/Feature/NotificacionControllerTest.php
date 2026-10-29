<?php

use App\Enums\Role;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAulaDocente;
use App\Models\Notificacion;
use App\Models\NotificacionDestinatario;
use App\Models\Persona;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

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
 *     docenteAjeno: User
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

it('lets a teacher write to their students, those parents and other teachers', function () {
    $escenario = escenarioNotificaciones();

    $this->actingAs($escenario['docente'])
        ->get(route('notificaciones.create'))
        ->assertOk()
        ->assertSee('Buscar y seleccionar varios')
        ->assertSee('Puedes marcar varios y filtrar por nombre, rol, aula o alumno en la misma lista.')
        ->assertSee('Herrera Ana (Alumno)')
        ->assertSee('Herrera Carlos (Padre de Herrera Ana)')
        ->assertSee('Castro Ana (Alumno)')
        ->assertSee('Castro Luis (Padre de Castro Ana)')
        ->assertSee('Colega Bruno (Docente de 8vo Básica B)')
        ->assertSee('font-semibold text-slate-800', false)
        ->assertSee('x-text="opcion.nombre"', false)
        ->assertDontSee('Ortiz Eva')
        ->assertDontSee('Ajeno Marta');
});

it('shows every assigned role next to each recipient name', function () {
    $escenario = escenarioNotificaciones();
    assignRole($escenario['colega'], Role::Padre);

    $this->actingAs($escenario['docente'])
        ->get(route('notificaciones.create'))
        ->assertOk()
        ->assertSee('Colega Bruno (Docente de 8vo Básica B, Padre)');
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

it('lets a teacher send a notification to a student, a parent and another teacher', function () {
    $escenario = escenarioNotificaciones();

    $this->actingAs($escenario['docente'])
        ->post(route('notificaciones.store'), [
            'destinatarios' => [
                $escenario['cuentaAlumno']->id,
                $escenario['padre']->id,
                $escenario['colega']->id,
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
            $escenario['colega']->id,
        ])->sort()->values()->all());
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
        'remitente_id' => $escenario['admin']->id,
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
            'destinatarios' => [$escenario['admin']->id],
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
        'user_id' => $escenario['admin']->id,
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
