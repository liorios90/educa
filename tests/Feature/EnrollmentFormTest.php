<?php

use App\Auth\ActiveRole;
use App\Enums\Role;
use App\Models\EnrollmentForm;
use App\Models\EnrollmentSubmission;
use App\Models\Establecimiento;
use App\Models\User;

function enrollmentAdminContext(Establecimiento $school, string $inicio = '2025-09-01', string $fin = '2026-06-30'): array
{
    $oferta = ofertaDe($school);
    $periodo = periodoActivoDe($oferta, [
        'nombre' => 'Periodo '.substr($inicio, 0, 4),
        'fecha_inicio' => $inicio,
        'fecha_fin' => $fin,
    ]);

    return [
        ...activeContextSession($oferta, $periodo),
        ActiveRole::SESSION_KEY => Role::Admin->value,
        'periodo' => $periodo,
    ];
}

it('lists period-specific forms and opens the section and subsection tab designer', function () {
    $school = Establecimiento::factory()->create();
    $admin = assignRole(User::factory()->create(['establecimiento_id' => $school->id]), Role::Admin);
    $context = enrollmentAdminContext($school);
    $this->actingAs($admin)->withSession($context)
        ->get(route('Admin.matriculas.index'))
        ->assertOk()
        ->assertSee('Formularios de matrícula')
        ->assertSee('Crear formulario');

    $this->actingAs($admin)->withSession($context)
        ->post(route('Admin.matriculas.forms.store'), ['periodo_id' => $context['periodo']->id, 'title' => 'Matrícula ordinaria'])
        ->assertRedirect();
    $form = EnrollmentForm::query()->where('establecimiento_id', $school->id)->firstOrFail();

    $this->actingAs($admin)->withSession($context)
        ->get(route('Admin.matriculas.forms.edit', $form))
        ->assertOk()
        ->assertSee('Datos representante')
        ->assertSee('Datos familiares')
        ->assertSee('role="tablist"', false)
        ->assertSee('role="tabpanel"', false);

    expect($form->sections)->toHaveCount(3);
});

it('lets administrators manage multiple forms but keeps only one active per period', function () {
    $school = Establecimiento::factory()->create();
    $admin = assignRole(User::factory()->create(['establecimiento_id' => $school->id]), Role::Admin);
    $context = enrollmentAdminContext($school);
    $this->actingAs($admin)->withSession($context)->post(route('Admin.matriculas.forms.store'), [
        'periodo_id' => $context['periodo']->id,
        'title' => 'Formulario base',
    ]);
    $baseForm = EnrollmentForm::query()->where('establecimiento_id', $school->id)->firstOrFail();

    $this->actingAs($admin)->withSession($context)->post(route('Admin.matriculas.forms.store'), [
        'periodo_id' => $context['periodo']->id,
        'title' => 'Formulario alternativo',
    ])->assertRedirect();
    $alternateForm = EnrollmentForm::query()->where('establecimiento_id', $school->id)->where('title', 'Formulario alternativo')->firstOrFail();

    expect($baseForm->fresh()->is_active)->toBeTrue()
        ->and($alternateForm->is_active)->toBeFalse();

    $this->actingAs($admin)->withSession($context)
        ->patch(route('Admin.matriculas.forms.activate', $alternateForm))
        ->assertRedirect();

    expect($alternateForm->fresh()->is_active)->toBeTrue()
        ->and($baseForm->fresh()->is_active)->toBeFalse()
        ->and(EnrollmentForm::query()->where('establecimiento_id', $school->id)->whereDate('periodo_inicio', '2025-09-01')->where('is_active', true)->count())->toBe(1);

    $this->actingAs($admin)->withSession($context)
        ->patch(route('Admin.matriculas.forms.update', $alternateForm), ['title' => 'Formulario actualizado'])
        ->assertRedirect();
    expect($alternateForm->fresh()->title)->toBe('Formulario actualizado');

    $this->actingAs($admin)->withSession($context)
        ->delete(route('Admin.matriculas.forms.destroy', $baseForm))
        ->assertRedirect();
    $this->assertDatabaseMissing('enrollment_forms', ['id' => $baseForm->id]);
});

it('lets an administrator add custom sections and required fields to a selected form', function () {
    $school = Establecimiento::factory()->create();
    $admin = assignRole(User::factory()->create(['establecimiento_id' => $school->id]), Role::Admin);
    $context = enrollmentAdminContext($school);
    $this->actingAs($admin)->withSession($context)->post(route('Admin.matriculas.forms.store'), [
        'periodo_id' => $context['periodo']->id,
        'title' => 'Matrícula',
    ]);
    $form = EnrollmentForm::query()->where('establecimiento_id', $school->id)->firstOrFail();

    $this->actingAs($admin)->withSession($context)
        ->post(route('Admin.matriculas.sections.store', $form), ['title' => 'Información médica'])
        ->assertRedirect();
    $section = $form->sections()->where('title', 'Información médica')->firstOrFail();

    $this->actingAs($admin)->withSession($context)
        ->get(route('Admin.matriculas.forms.edit', $form))
        ->assertOk()
        ->assertSee(route('Admin.matriculas.fields.store', [$form, $section]), false)
        ->assertSee('Campos directos de esta sección');

    $this->actingAs($admin)->withSession($context)
        ->post(route('Admin.matriculas.fields.store', [$form, $section]), [
            'label' => 'Alergias',
            'type' => 'textarea',
            'is_required' => '1',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('enrollment_fields', ['enrollment_section_id' => $section->id, 'label' => 'Alergias', 'is_required' => true]);
});

it('lets a parent submit and edit one form for each child in the active period', function () {
    $school = Establecimiento::factory()->create();
    $admin = assignRole(User::factory()->create(['establecimiento_id' => $school->id]), Role::Admin);
    $context = enrollmentAdminContext($school);
    $this->actingAs($admin)->withSession($context)->post(route('Admin.matriculas.forms.store'), [
        'periodo_id' => $context['periodo']->id,
        'title' => 'Matrícula activa',
    ]);
    $form = EnrollmentForm::query()->where('establecimiento_id', $school->id)->firstOrFail();
    $section = $form->sections()->firstOrFail();
    $requiredField = $section->fields()->create(['label' => 'Referencia', 'type' => 'text', 'is_required' => true]);
    $studentFields = $form->sections()->where('title', 'Datos del estudiante')->firstOrFail()->subsections()->firstOrFail()->fields()->get()->keyBy('label');
    $parent = assignRole(User::factory()->create(['establecimiento_id' => $school->id]), Role::Padre);

    $this->actingAs($parent)
        ->get(route('matriculas.create'))
        ->assertOk()
        ->assertSee('Periodo: '.$context['periodo']->nombre)
        ->assertSee('role="tablist"', false)
        ->assertSee('Secciones de la ficha', false)
        ->assertSee('Subsecciones de Datos representante', false)
        ->assertSee('Apellidos completos');

    $firstChild = [
        $requiredField->id => 'Ficha uno',
        $studentFields['Apellidos completos']->id => 'Pérez',
        $studentFields['Nombres completos']->id => 'Ana',
    ];
    $this->actingAs($parent)->post(route('matriculas.store'), ['responses' => [$requiredField->id => '']])
        ->assertSessionHasErrors('responses.'.$requiredField->id);
    $this->actingAs($parent)->post(route('matriculas.store'), ['responses' => $firstChild])
        ->assertRedirect(route('matriculas.index'))
        ->assertSessionHas('status', 'matricula-submitted');

    $firstSubmission = EnrollmentSubmission::query()->where('user_id', $parent->id)->firstOrFail();
    $this->actingAs($parent)->get(route('matriculas.edit', $firstSubmission))
        ->assertOk()
        ->assertSee('Editar ficha de Pérez Ana')
        ->assertSee('value="Ana"', false);

    $this->actingAs($parent)->put(route('matriculas.update', $firstSubmission), ['responses' => [
        ...$firstChild,
        $studentFields['Nombres completos']->id => 'Ana María',
    ]])->assertRedirect(route('matriculas.index'))->assertSessionHas('status', 'matricula-updated');

    $this->actingAs($parent)->post(route('matriculas.store'), ['responses' => [
        $requiredField->id => 'Ficha dos',
        $studentFields['Apellidos completos']->id => 'Pérez',
        $studentFields['Nombres completos']->id => 'Luis',
    ]])->assertRedirect(route('matriculas.index'))->assertSessionHas('status', 'matricula-submitted');

    $this->actingAs($parent)->get(route('matriculas.index'))
        ->assertOk()
        ->assertSee('Ana María')
        ->assertSee('Pérez Luis')
        ->assertSee('Editar ficha');

    expect(EnrollmentSubmission::query()->where('user_id', $parent->id)->where('enrollment_form_id', $form->id)->count())->toBe(2);
});

it('copies the previous period structure into a new period form', function () {
    $school = Establecimiento::factory()->create();
    $admin = assignRole(User::factory()->create(['establecimiento_id' => $school->id]), Role::Admin);
    $priorContext = enrollmentAdminContext($school);
    $this->actingAs($admin)->withSession($priorContext)->post(route('Admin.matriculas.forms.store'), [
        'periodo_id' => $priorContext['periodo']->id,
        'title' => 'Formulario anterior',
    ]);
    $priorForm = EnrollmentForm::query()->where('establecimiento_id', $school->id)->firstOrFail();
    $customSection = $priorForm->sections()->create(['title' => 'Datos médicos', 'position' => 9]);
    $customSection->fields()->create([
        'label' => 'Alergias',
        'type' => 'select',
        'options' => ['Polen', 'Ninguna'],
        'is_required' => true,
        'position' => 1,
    ]);

    $newContext = enrollmentAdminContext($school, '2026-09-01', '2027-06-30');
    $this->actingAs($admin)->withSession($newContext)->post(route('Admin.matriculas.forms.store'), [
        'periodo_id' => $newContext['periodo']->id,
        'title' => 'Formulario nuevo periodo',
    ])->assertRedirect();

    $newForm = EnrollmentForm::query()->where('establecimiento_id', $school->id)->whereDate('periodo_inicio', '2026-09-01')->firstOrFail();
    $clonedField = $newForm->sections()->where('title', 'Datos médicos')->firstOrFail()->fields()->firstOrFail();

    expect($newForm->id)->not->toBe($priorForm->id)
        ->and($newForm->is_active)->toBeTrue()
        ->and($clonedField->options)->toBe(['Polen', 'Ninguna'])
        ->and($clonedField->is_required)->toBeTrue();
});

it('prevents administrators from changing a different school form', function () {
    $school = Establecimiento::factory()->create();
    $otherSchool = Establecimiento::factory()->create();
    $admin = assignRole(User::factory()->create(['establecimiento_id' => $school->id]), Role::Admin);
    $otherAdmin = assignRole(User::factory()->create(['establecimiento_id' => $otherSchool->id]), Role::Admin);
    $otherContext = enrollmentAdminContext($otherSchool);
    $this->actingAs($otherAdmin)->withSession($otherContext)->post(route('Admin.matriculas.forms.store'), [
        'periodo_id' => $otherContext['periodo']->id,
        'title' => 'Formulario ajeno',
    ]);
    $foreignForm = EnrollmentForm::query()->where('establecimiento_id', $otherSchool->id)->firstOrFail();
    $foreignSection = $foreignForm->sections()->firstOrFail();
    $ownContext = enrollmentAdminContext($school);

    $this->actingAs($admin)->withSession($ownContext)
        ->delete(route('Admin.matriculas.sections.destroy', $foreignSection))
        ->assertNotFound();

    $this->assertDatabaseHas('enrollment_sections', ['id' => $foreignSection->id]);
});
