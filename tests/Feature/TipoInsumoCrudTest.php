<?php

use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoGrado;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Grado;
use App\Models\TipoInsumo;
use Illuminate\Support\Facades\DB;

it('allows an admin to create, list, update, and delete types of supply', function (): void {
    $admin = adminOf(Establecimiento::factory()->create());

    $this->actingAs($admin)
        ->post(route('Admin.tipos-insumos.store'), [
            'nombre' => 'Tarea',
            'descripcion' => 'Trabajo de aula',
            'tipo_calificacion' => 'cuantitativa',
            'activo' => '1',
        ])
        ->assertRedirect(route('Admin.tipos-insumos.index'));

    $tipoInsumo = TipoInsumo::query()->firstOrFail();
    $this->assertDatabaseHas('tipos_insumos', ['id' => $tipoInsumo->id, 'nombre' => 'Tarea']);

    $this->actingAs($admin)
        ->get(route('Admin.tipos-insumos.index'))
        ->assertOk()
        ->assertSee('Tarea')
        ->assertSee('Asignaturas')
        ->assertSee('Cuantitativa');

    $this->actingAs($admin)
        ->patch(route('Admin.tipos-insumos.update', $tipoInsumo), [
            'nombre' => 'Proyecto',
            'descripcion' => 'Proyecto final',
            'tipo_calificacion' => 'cualitativa',
            'activo' => '0',
        ])
        ->assertRedirect(route('Admin.tipos-insumos.index'));

    $this->assertDatabaseHas('tipos_insumos', ['id' => $tipoInsumo->id, 'nombre' => 'Proyecto', 'activo' => false]);

    $this->actingAs($admin)
        ->delete(route('Admin.tipos-insumos.destroy', $tipoInsumo))
        ->assertRedirect(route('Admin.tipos-insumos.index'));

    $this->assertDatabaseMissing('tipos_insumos', ['id' => $tipoInsumo->id]);
});

it('assigns classroom subjects to a supply type and removes the assignment when unchecked', function (): void {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $grado = Sys_Grado::factory()->create(['nombre' => 'Segundo de inicial']);
    $gradoEstablecimiento = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $grado->id,
    ]);
    $aula = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $gradoEstablecimiento->id,
        'paralelo' => 'A',
    ]);
    $aulaParaleloB = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $gradoEstablecimiento->id,
        'paralelo' => 'B',
    ]);
    $asignatura = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $grado->id,
        'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Experiencia de Aprendizaje'])->id,
    ]);
    $tipoInsumo = TipoInsumo::query()->create([
        'nombre' => 'Evaluación inicial',
        'tipo_calificacion' => 'cuantitativa',
        'activo' => true,
    ]);
    $otroTipoInsumo = TipoInsumo::query()->create([
        'nombre' => 'Tarea',
        'tipo_calificacion' => 'cuantitativa',
        'activo' => true,
    ]);
    DB::table('establecimiento_aula_asignatura_tipo_insumos')->insert([
        'establecimiento_aula_id' => $aulaParaleloB->id,
        'establecimiento_asignatura_id' => $asignatura->id,
        'tipo_insumo_id' => $otroTipoInsumo->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->get(route('Admin.tipos-insumos.asignaturas.index', $tipoInsumo))
        ->assertOk()
        ->assertSee('Experiencia de Aprendizaje')
        ->assertSee('Segundo de inicial A')
        ->assertSee('Tipo asignado')
        ->assertSee('Pertenece a este tipo')
        ->assertSee('orden=asignatura', false)
        ->assertSee('value="'.$aula->id.':'.$asignatura->id.'"', false)
        ->assertSee('value="'.$aulaParaleloB->id.':'.$asignatura->id.'"', false)
        ->assertSee('Tarea')
        ->assertSee('checked', false)
        ->assertSee('disabled', false)
        ->assertSee('Segundo de inicial B');

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->from(route('Admin.tipos-insumos.asignaturas.index', $tipoInsumo))
        ->put(route('Admin.tipos-insumos.asignaturas.update', $tipoInsumo), [
            'visibles' => [$aulaParaleloB->id.':'.$asignatura->id],
            'asignaciones' => [$aulaParaleloB->id.':'.$asignatura->id],
        ])
        ->assertSessionHasErrors('asignaciones');

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->put(route('Admin.tipos-insumos.asignaturas.update', $tipoInsumo), [
            'asignaciones' => [$aula->id.':'.$asignatura->id],
        ])
        ->assertRedirect(route('Admin.tipos-insumos.asignaturas.index', $tipoInsumo));

    $this->assertDatabaseHas('establecimiento_aula_asignatura_tipo_insumos', [
        'establecimiento_aula_id' => $aula->id,
        'establecimiento_asignatura_id' => $asignatura->id,
        'tipo_insumo_id' => $tipoInsumo->id,
    ]);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->get(route('Admin.tipos-insumos.asignaturas.index', $tipoInsumo))
        ->assertSee('checked', false)
        ->assertSee('Evaluación inicial');

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->put(route('Admin.tipos-insumos.asignaturas.update', $tipoInsumo), [])
        ->assertRedirect(route('Admin.tipos-insumos.asignaturas.index', $tipoInsumo));

    $this->assertDatabaseMissing('establecimiento_aula_asignatura_tipo_insumos', [
        'establecimiento_aula_id' => $aula->id,
        'establecimiento_asignatura_id' => $asignatura->id,
    ]);

    $this->assertDatabaseHas('establecimiento_aula_asignatura_tipo_insumos', [
        'establecimiento_aula_id' => $aulaParaleloB->id,
        'establecimiento_asignatura_id' => $asignatura->id,
        'tipo_insumo_id' => $otroTipoInsumo->id,
    ]);
});

it('paginates and sorts classroom subjects without removing assignments from other pages', function (): void {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $grado = Sys_Grado::factory()->create();
    $gradoEstablecimiento = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $grado->id,
    ]);
    $aula = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $gradoEstablecimiento->id,
    ]);
    $asignaturas = collect(range(1, 16))->map(fn (int $numero): EstablecimientoAsignatura => EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $grado->id,
        'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Materia '.$numero])->id,
    ]));
    $tipoInsumo = TipoInsumo::query()->create([
        'nombre' => 'Evaluación inicial',
        'tipo_calificacion' => 'cuantitativa',
        'activo' => true,
    ]);
    $asignaturaEnOtraPagina = $asignaturas->last();
    DB::table('establecimiento_aula_asignatura_tipo_insumos')->insert([
        'establecimiento_aula_id' => $aula->id,
        'establecimiento_asignatura_id' => $asignaturaEnOtraPagina->id,
        'tipo_insumo_id' => $tipoInsumo->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->get(route('Admin.tipos-insumos.asignaturas.index', $tipoInsumo))
        ->assertOk()
        ->assertSee('Mostrando 1–15 de 16 asignaturas.')
        ->assertSee('page=2', false)
        ->assertSee('orden=asignatura', false);

    $visiblesPrimeraPagina = $asignaturas->take(15)->map(fn (EstablecimientoAsignatura $asignatura): string => $aula->id.':'.$asignatura->id)->all();
    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->put(route('Admin.tipos-insumos.asignaturas.update', $tipoInsumo), [
            'visibles' => $visiblesPrimeraPagina,
            'asignaciones' => [],
            'orden' => 'id',
            'direccion' => 'asc',
            'pagina' => 1,
        ])
        ->assertRedirect(route('Admin.tipos-insumos.asignaturas.index', ['tipoInsumo' => $tipoInsumo, 'orden' => 'id', 'direccion' => 'asc', 'page' => 1]));

    $this->assertDatabaseHas('establecimiento_aula_asignatura_tipo_insumos', [
        'establecimiento_aula_id' => $aula->id,
        'establecimiento_asignatura_id' => $asignaturaEnOtraPagina->id,
        'tipo_insumo_id' => $tipoInsumo->id,
    ]);
});

it('rejects assigning a classroom subject from another establishment', function (): void {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $tipoInsumo = TipoInsumo::query()->create([
        'nombre' => 'Evaluación inicial',
        'tipo_calificacion' => 'cuantitativa',
        'activo' => true,
    ]);
    $establecimientoAjeno = Establecimiento::factory()->create();
    $ofertaAjena = ofertaDe($establecimientoAjeno);
    $periodoAjeno = periodoActivoDe($ofertaAjena);
    $gradoAjeno = Sys_Grado::factory()->create();
    $gradoEstablecimientoAjeno = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimientoAjeno->id,
        'establecimiento_modalidad_jornada_id' => $ofertaAjena->id,
        'grado_id' => $gradoAjeno->id,
    ]);
    $aulaAjena = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimientoAjeno->id,
        'establecimiento_modalidad_jornada_id' => $ofertaAjena->id,
        'establecimiento_periodo_id' => $periodoAjeno->id,
        'establecimiento_grado_id' => $gradoEstablecimientoAjeno->id,
    ]);
    $asignaturaAjena = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimientoAjeno->id,
        'establecimiento_modalidad_jornada_id' => $ofertaAjena->id,
        'grado_id' => $gradoAjeno->id,
    ]);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->from(route('Admin.tipos-insumos.asignaturas.index', $tipoInsumo))
        ->put(route('Admin.tipos-insumos.asignaturas.update', $tipoInsumo), [
            'asignaciones' => [$aulaAjena->id.':'.$asignaturaAjena->id],
        ])
        ->assertSessionHasErrors('asignaciones');

    $this->assertDatabaseMissing('establecimiento_aula_asignatura_tipo_insumos', [
        'establecimiento_aula_id' => $aulaAjena->id,
        'establecimiento_asignatura_id' => $asignaturaAjena->id,
    ]);
});

it('rejects an unsupported grading type', function (): void {
    $admin = adminOf(Establecimiento::factory()->create());

    $this->actingAs($admin)
        ->from(route('Admin.tipos-insumos.create'))
        ->post(route('Admin.tipos-insumos.store'), [
            'nombre' => 'Tarea',
            'tipo_calificacion' => 'mixta',
            'activo' => '1',
        ])
        ->assertSessionHasErrors('tipo_calificacion');

    $this->assertDatabaseCount('tipos_insumos', 0);
});

it('stores qualitative detail values on the same detail screen', function (): void {
    $admin = adminOf(Establecimiento::factory()->create());
    $tipoInsumo = TipoInsumo::query()->create([
        'nombre' => 'Evaluación cualitativa',
        'tipo_calificacion' => 'cualitativa',
        'activo' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('Admin.tipos-insumos.detalles.store', $tipoInsumo), [
            'nombre' => 'A+',
            'descripcion' => 'Destreza alcanzada',
            'valor' => 'A+',
            'activo' => '1',
        ])
        ->assertRedirect(route('Admin.tipos-insumos.detalles.index', $tipoInsumo));

    $this->assertDatabaseHas('tipos_insumos_detalles', [
        'tipo_insumo_id' => $tipoInsumo->id,
        'nombre' => 'A+',
        'valor' => 'A+',
        'porcentaje' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('Admin.tipos-insumos.detalles.index', $tipoInsumo))
        ->assertOk()
        ->assertSee('Detalles creados')
        ->assertSee('open-modal')
        ->assertSee('tipo-insumo-detalle-create')
        ->assertSee('Agregar detalle')
        ->assertSee('Destreza alcanzada');
});

it('shows the quantitative active percentage total without blocking incomplete or excessive totals', function (): void {
    $admin = adminOf(Establecimiento::factory()->create());
    $tipoInsumo = TipoInsumo::query()->create([
        'nombre' => 'Evaluación cuantitativa',
        'tipo_calificacion' => 'cuantitativa',
        'activo' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('Admin.tipos-insumos.detalles.store', $tipoInsumo), [
            'nombre' => 'Tareas',
            'porcentaje' => '60',
            'activo' => '1',
        ])
        ->assertRedirect();

    $activo = $tipoInsumo->detalles()->where('nombre', 'Tareas')->firstOrFail();
    $this->actingAs($admin)
        ->post(route('Admin.tipos-insumos.detalles.store', $tipoInsumo), [
            'nombre' => 'Inactivo',
            'porcentaje' => '99',
            'activo' => '0',
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->get(route('Admin.tipos-insumos.detalles.index', $tipoInsumo))
        ->assertOk()
        ->assertSee('Falta')
        ->assertSee('40.00')
        ->assertSee('99.00');

    $this->actingAs($admin)
        ->patch(route('Admin.tipos-insumos.detalles.update', [$tipoInsumo, $activo]), [
            'nombre' => 'Tareas',
            'porcentaje' => '100',
            'activo' => '1',
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('Admin.tipos-insumos.detalles.store', $tipoInsumo), [
            'nombre' => 'Examen',
            'porcentaje' => '1',
            'activo' => '1',
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->get(route('Admin.tipos-insumos.detalles.index', $tipoInsumo))
        ->assertOk()
        ->assertSee('supera el 100%')
        ->assertSee('1.00');
});
