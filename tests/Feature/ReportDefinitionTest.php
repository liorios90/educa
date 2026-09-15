<?php

use App\Enums\Role;
use App\Models\ReportDefinition;
use App\Models\ReportDefinitionField;
use App\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

it('sends related table fields from the designer page', function () {
    $actor = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($actor)
        ->get(route('sistemas.reports.create'))
        ->assertOk()
        ->assertSee('roles.name', false)
        ->assertSee('Campos disponibles (tabla y relaciones)')
        ->assertSee('Vista previa');
});

it('saves free positions for descriptions and fields', function () {
    $actor = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($actor)
        ->post(route('sistemas.reports.store'), [
            'name' => 'Hoja de jornadas',
            'source' => 'jornadas',
            'is_active' => '1',
            'visible_to_all' => '1',
            'fields' => [
                [
                    'column' => 'nombre',
                    'label' => 'Jornada',
                    'label_x' => 12,
                    'label_y' => 20,
                    'value_x' => 40,
                    'value_y' => 55,
                ],
            ],
        ])
        ->assertRedirect(route('sistemas.reports.index'));

    $this->assertDatabaseHas('report_definition_fields', [
        'column' => 'nombre',
        'label' => 'Jornada',
        'label_x' => 12,
        'label_y' => 20,
        'value_x' => 40,
        'value_y' => 55,
    ]);
});

it('rejects layout positions outside the canvas', function () {
    $actor = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($actor)
        ->from(route('sistemas.reports.create'))
        ->post(route('sistemas.reports.store'), [
            'name' => 'Fuera de hoja',
            'source' => 'jornadas',
            'is_active' => '1',
            'visible_to_all' => '1',
            'fields' => [
                [
                    'column' => 'nombre',
                    'label' => 'Jornada',
                    'label_x' => 200,
                    'label_y' => 8,
                    'value_x' => 32,
                    'value_y' => 8,
                ],
            ],
        ])
        ->assertRedirect(route('sistemas.reports.create'))
        ->assertSessionHasErrors('fields.0.label_x');

    $this->assertDatabaseMissing('report_definitions', ['name' => 'Fuera de hoja']);
});

it('allows saving a field from a related table', function () {
    $actor = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($actor)
        ->post(route('sistemas.reports.store'), [
            'name' => 'Usuarios y roles',
            'source' => 'usuarios',
            'is_active' => '1',
            'visible_to_all' => '1',
            'fields' => [
                ['column' => 'name', 'label' => 'Usuario'],
                ['column' => 'roles.name', 'label' => 'Roles'],
            ],
        ])
        ->assertRedirect(route('sistemas.reports.index'));

    $this->assertDatabaseHas('report_definition_fields', [
        'column' => 'roles.name',
        'label' => 'Roles',
    ]);
});

it('allows systems users to save a report with ordered fields', function () {
    $actor = assignRole(User::factory()->create(), Role::Sistemas);
    $sistemasRole = RoleModel::findOrCreate(Role::Sistemas->value, 'web');

    $this->actingAs($actor)
        ->post(route('sistemas.reports.store'), [
            'name' => 'Listado de jornadas',
            'source' => 'jornadas',
            'is_active' => '1',
            'visible_to_all' => '0',
            'roles' => [$sistemasRole->id],
            'fields' => [
                ['column' => 'descripcion', 'label' => 'Detalle'],
                ['column' => 'nombre', 'label' => 'Jornada'],
            ],
        ])
        ->assertRedirect(route('sistemas.reports.index'))
        ->assertSessionHas('status', 'report-created');

    $report = ReportDefinition::query()->where('name', 'Listado de jornadas')->firstOrFail();

    expect($report->source)->toBe('jornadas')
        ->and($report->fields()->orderBy('sort_order')->pluck('column')->all())->toBe(['descripcion', 'nombre']);
});

it('rejects fields that do not belong to the selected table', function () {
    $actor = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($actor)
        ->from(route('sistemas.reports.create'))
        ->post(route('sistemas.reports.store'), [
            'name' => 'Inválido',
            'source' => 'jornadas',
            'is_active' => '1',
            'visible_to_all' => '1',
            'fields' => [
                ['column' => 'password', 'label' => 'Clave'],
            ],
        ])
        ->assertRedirect(route('sistemas.reports.create'))
        ->assertSessionHasErrors('fields.0.column');

    $this->assertDatabaseMissing('report_definitions', ['name' => 'Inválido']);
});

it('rejects an unknown source table', function () {
    $actor = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($actor)
        ->from(route('sistemas.reports.create'))
        ->post(route('sistemas.reports.store'), [
            'name' => 'Hack',
            'source' => 'users',
            'is_active' => '1',
            'visible_to_all' => '1',
            'fields' => [
                ['column' => 'email', 'label' => 'Correo'],
            ],
        ])
        ->assertRedirect(route('sistemas.reports.create'))
        ->assertSessionHasErrors('source');
});

it('requires at least one field', function () {
    $actor = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($actor)
        ->from(route('sistemas.reports.create'))
        ->post(route('sistemas.reports.store'), [
            'name' => 'Vacío',
            'source' => 'jornadas',
            'is_active' => '1',
            'visible_to_all' => '1',
            'fields' => [],
        ])
        ->assertRedirect(route('sistemas.reports.create'))
        ->assertSessionHasErrors('fields');
});

it('forbids administrators from designing reports', function () {
    $actor = assignRole(User::factory()->create(), Role::Admin);

    $this->actingAs($actor)
        ->get(route('sistemas.reports.index'))
        ->assertForbidden();
});

it('updates field order and labels', function () {
    $actor = assignRole(User::factory()->create(), Role::Sistemas);
    $report = ReportDefinition::factory()->visibleToAll()->create(['source' => 'jornadas']);
    ReportDefinitionField::factory()->create([
        'report_definition_id' => $report->id,
        'column' => 'nombre',
        'label' => 'Nombre',
        'sort_order' => 0,
    ]);

    $this->actingAs($actor)
        ->patch(route('sistemas.reports.update', $report), [
            'name' => $report->name,
            'source' => 'jornadas',
            'is_active' => '1',
            'visible_to_all' => '1',
            'fields' => [
                ['column' => 'nombre', 'label' => 'Turno'],
                ['column' => 'descripcion', 'label' => 'Notas'],
            ],
        ])
        ->assertRedirect(route('sistemas.reports.index'));

    expect($report->fields()->orderBy('sort_order')->pluck('label')->all())->toBe(['Turno', 'Notas']);
});

it('deletes a report definition', function () {
    $actor = assignRole(User::factory()->create(), Role::Sistemas);
    $report = ReportDefinition::factory()->create();

    $this->actingAs($actor)
        ->delete(route('sistemas.reports.destroy', $report))
        ->assertRedirect(route('sistemas.reports.index'));

    $this->assertModelMissing($report);
});
