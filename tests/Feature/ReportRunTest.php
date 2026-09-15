<?php

use App\Enums\Role;
use App\Models\ReportDefinition;
use App\Models\ReportDefinitionField;
use App\Models\Sys_Jornada;
use App\Models\User;

it('generates values from related tables', function () {
    $user = assignRole(User::factory()->create(['name' => 'Ana Sistemas']), Role::Sistemas);
    assignRole(User::factory()->create(['name' => 'Luisa Secretaria']), Role::Secretaria);

    $report = ReportDefinition::factory()->visibleToAll()->create([
        'name' => 'Directorio',
        'source' => 'usuarios',
    ]);
    ReportDefinitionField::factory()->create([
        'report_definition_id' => $report->id,
        'column' => 'name',
        'label' => 'Usuario',
        'sort_order' => 0,
    ]);
    ReportDefinitionField::factory()->create([
        'report_definition_id' => $report->id,
        'column' => 'roles.name',
        'label' => 'Roles',
        'sort_order' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('reports.show', $report))
        ->assertOk()
        ->assertSee('Luisa Secretaria')
        ->assertSee('Secretaria');
});

it('renders saved description and field positions', function () {
    $user = assignRole(User::factory()->create(), Role::Sistemas);
    Sys_Jornada::factory()->create(['nombre' => 'Matutina']);

    $report = ReportDefinition::factory()->visibleToAll()->create([
        'name' => 'Hoja de jornadas',
        'source' => 'jornadas',
    ]);
    ReportDefinitionField::factory()->create([
        'report_definition_id' => $report->id,
        'column' => 'nombre',
        'label' => 'Jornada',
        'sort_order' => 0,
        'label_x' => 18,
        'label_y' => 22,
        'value_x' => 45,
        'value_y' => 60,
    ]);

    $this->actingAs($user)
        ->get(route('reports.show', $report))
        ->assertSee('Jornada')
        ->assertSee('Matutina')
        ->assertSee('left: 18%; top: 22%;', false)
        ->assertSee('left: 45%; top: 60%;', false);
});

it('lets an allowed user download the report as pdf', function () {
    $user = assignRole(User::factory()->create(), Role::Sistemas);
    Sys_Jornada::factory()->create(['nombre' => 'Matutina']);

    $report = ReportDefinition::factory()->visibleToAll()->create([
        'name' => 'Hoja de jornadas',
        'source' => 'jornadas',
    ]);
    ReportDefinitionField::factory()->create([
        'report_definition_id' => $report->id,
        'column' => 'nombre',
        'label' => 'Jornada',
        'sort_order' => 0,
        'label_x' => 18,
        'label_y' => 22,
        'value_x' => 45,
        'value_y' => 60,
    ]);

    $this->actingAs($user)
        ->get(route('reports.show', $report))
        ->assertSee('Descargar PDF');

    $this->actingAs($user)
        ->get(route('reports.pdf', $report))
        ->assertDownload('hoja-de-jornadas.pdf')
        ->assertHeader('content-type', 'application/pdf');
});

it('escapes report values in the pdf', function () {
    $user = assignRole(User::factory()->create(), Role::Sistemas);
    Sys_Jornada::factory()->create(['nombre' => "<script>alert('xss')</script>"]);

    $report = ReportDefinition::factory()->visibleToAll()->create([
        'name' => 'Jornadas',
        'source' => 'jornadas',
    ]);
    ReportDefinitionField::factory()->create([
        'report_definition_id' => $report->id,
        'column' => 'nombre',
        'label' => 'Jornada',
        'sort_order' => 0,
    ]);

    $this->view('reports.pdf', [
        'report' => $report->load('fields'),
        'rows' => [
            ['nombre' => "<script>alert('xss')</script>"],
        ],
    ])
        ->assertSee("<script>alert('xss')</script>")
        ->assertDontSee("<script>alert('xss')</script>", false);

    $this->actingAs($user)
        ->get(route('reports.pdf', $report))
        ->assertDownload();
});

it('hides the pdf of reports the user cannot see', function () {
    $admin = assignRole(User::factory()->create(), Role::Admin);
    $sistemas = assignRole(User::factory()->create(), Role::Sistemas);

    $report = ReportDefinition::factory()->create(['name' => 'Solo sistemas', 'visible_to_all' => false]);
    $report->roles()->sync($sistemas->roles->pluck('id'));
    ReportDefinitionField::factory()->create(['report_definition_id' => $report->id]);

    $this->actingAs($admin)
        ->get(route('reports.pdf', $report))
        ->assertNotFound();
});

it('lets an allowed user generate a saved report', function () {
    $user = assignRole(User::factory()->create(), Role::Sistemas);
    Sys_Jornada::factory()->create([
        'nombre' => 'Matutina',
        'descripcion' => 'Mañana',
    ]);

    $report = ReportDefinition::factory()->visibleToAll()->create([
        'name' => 'Jornadas activas',
        'source' => 'jornadas',
    ]);
    ReportDefinitionField::factory()->create([
        'report_definition_id' => $report->id,
        'column' => 'nombre',
        'label' => 'Jornada',
        'sort_order' => 0,
    ]);
    ReportDefinitionField::factory()->create([
        'report_definition_id' => $report->id,
        'column' => 'descripcion',
        'label' => 'Detalle',
        'sort_order' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('reports.show', $report))
        ->assertOk()
        ->assertSee('Jornadas activas')
        ->assertSee('Jornada')
        ->assertSee('Detalle')
        ->assertSee('Matutina')
        ->assertSee('Mañana');
});

it('escapes report values', function () {
    $user = assignRole(User::factory()->create(), Role::Sistemas);
    Sys_Jornada::factory()->create(['nombre' => "<script>alert('xss')</script>"]);

    $report = ReportDefinition::factory()->visibleToAll()->create(['source' => 'jornadas']);
    ReportDefinitionField::factory()->create([
        'report_definition_id' => $report->id,
        'column' => 'nombre',
        'label' => 'Jornada',
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('reports.show', $report))
        ->assertSee("<script>alert('xss')</script>")
        ->assertDontSee("<script>alert('xss')</script>", false);
});

it('hides reports the user cannot see', function () {
    $admin = assignRole(User::factory()->create(), Role::Admin);
    $sistemas = assignRole(User::factory()->create(), Role::Sistemas);

    $report = ReportDefinition::factory()->create(['name' => 'Solo sistemas', 'visible_to_all' => false]);
    $report->roles()->sync($sistemas->roles->pluck('id'));
    ReportDefinitionField::factory()->create(['report_definition_id' => $report->id]);

    $this->actingAs($admin)
        ->get(route('reports.index'))
        ->assertDontSee('Solo sistemas');

    $this->actingAs($admin)
        ->get(route('reports.show', $report))
        ->assertNotFound();
});

it('redirects guests away from reports', function () {
    $this->get(route('reports.index'))
        ->assertRedirect(route('login'));

    $report = ReportDefinition::factory()->visibleToAll()->create();
    ReportDefinitionField::factory()->create(['report_definition_id' => $report->id]);

    $this->get(route('reports.pdf', $report))
        ->assertRedirect(route('login'));
});
