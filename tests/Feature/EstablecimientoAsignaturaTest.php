<?php

use App\Enums\ModoLibretaArea;
use App\Enums\Role;
use App\Enums\TipoCalificacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAreaLibreta;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoGrado;
use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

function catalogoAsignatura(array $estructura, array $nombres = []): Sys_Asignatura
{
    $area = Sys_Area::factory()->for($estructura['subnivel'], 'subnivel')->create([
        'nombre' => $nombres['area'] ?? 'Matemática',
    ]);

    return Sys_Asignatura::factory()->for($area, 'area')->create([
        'nombre' => $nombres['asignatura'] ?? 'Matemática',
        'horas_semanales' => $nombres['horas'] ?? 5,
        'aparece_en_libreta' => $nombres['libreta'] ?? true,
    ]);
}

function gradoEnOferta(Establecimiento $establecimiento, $oferta, array $estructura): EstablecimientoGrado
{
    return EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'subnivel_id' => $estructura['subnivel']->id,
        'grado_id' => $estructura['grado']->id,
    ]);
}

describe('index', function () {
    it('lists jornadas and links to the malla of each oferta', function () {
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'Unidad Educativa Andina']);
        $admin = adminOf($establecimiento);
        $matutina = ofertaDe($establecimiento);
        $vespertina = ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
        $estructura = catalogoEstructura();
        gradoEnOferta($establecimiento, $matutina, $estructura);

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($matutina))
            ->get(route('Admin.asignaturas'))
            ->assertOk()
            ->assertSee('Asignaturas')
            ->assertSee('Unidad Educativa Andina')
            ->assertSee('Presencial')
            ->assertSee('Matutina')
            ->assertSee('Vespertina')
            ->assertSee('Definir malla')
            ->assertSee('Sin asignaturas definidas')
            ->assertSee('Sin grados en la estructura')
            ->assertSee(route('Admin.asignaturas.edit', $matutina), false)
            ->assertSee(route('Admin.estructura.edit', $vespertina), false);
    });

    it('forbids systems users from opening establishment asignaturas', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->get(route('Admin.asignaturas'))
            ->assertForbidden();
    });

    it('redirects guests from establishment asignaturas to login', function () {
        $this->get(route('Admin.asignaturas'))
            ->assertRedirect(route('login'));
    });

    it('forbids administrators without an establishment', function () {
        $admin = assignRole(User::factory()->create(['establecimiento_id' => null]), Role::Admin);

        $this->actingAs($admin)
            ->get(route('Admin.asignaturas'))
            ->assertForbidden();
    });

    it('shows the asignaturas option to administrators', function () {
        $this->seed(NavigationSeeder::class);
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertSee('Asignaturas')
            ->assertSee(route('Admin.asignaturas'), false);
    });
});

describe('edit', function () {
    it('shows catalog areas and asignaturas of the grados offered by the jornada', function () {
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'Unidad Educativa Andina']);
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $estructura = catalogoEstructura([
            'nivel' => 'Educación General Básica',
            'subnivel' => 'Básica Elemental',
            'grado' => 'Segundo de EGB',
        ]);
        gradoEnOferta($establecimiento, $oferta, $estructura);
        $asignatura = catalogoAsignatura($estructura);

        $this->actingAs($admin)
            ->get(route('Admin.asignaturas.edit', $oferta))
            ->assertOk()
            ->assertSee('Unidad Educativa Andina')
            ->assertSee('Presencial')
            ->assertSee('Matutina')
            ->assertSee('Subnivel: Básica Elemental')
            ->assertSee('Área: Matemática')
            ->assertSee('Asignatura: Matemática')
            ->assertSee('Segundo de EGB')
            ->assertSee('Aparece en libreta')
            ->assertSee('Forma de calificar')
            ->assertSee('Horas semanales')
            ->assertSee('Guardar malla')
            ->assertSee(route('Admin.asignaturas.update', $oferta), false);
    });

    it('does not show asignaturas of a subnivel the jornada does not offer', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $propia = catalogoEstructura(['subnivel' => 'Básica Elemental']);
        $ajena = catalogoEstructura(['nivel' => 'Bachillerato', 'subnivel' => 'Bachillerato General Unificado', 'grado' => 'Primero de BGU']);
        gradoEnOferta($establecimiento, $oferta, $propia);
        catalogoAsignatura($ajena, ['area' => 'Filosofía', 'asignatura' => 'Filosofía']);

        $this->actingAs($admin)
            ->get(route('Admin.asignaturas.edit', $oferta))
            ->assertOk()
            ->assertSee('Básica Elemental')
            ->assertDontSee('Filosofía');
    });

    it('returns 404 when editing an oferta of another establishment', function () {
        $admin = adminOf(Establecimiento::factory()->create());
        $ajena = ofertaDe(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('Admin.asignaturas.edit', $ajena))
            ->assertNotFound();
    });

    it('shows the area report-card modes when the catalog area has several asignaturas', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $estructura = catalogoEstructura([
            'nivel' => 'Educación General Básica',
            'subnivel' => 'Básica Elemental',
            'grado' => 'Segundo de EGB',
        ]);
        gradoEnOferta($establecimiento, $oferta, $estructura);
        $matematica = catalogoAsignatura($estructura);
        Sys_Asignatura::factory()->for($matematica->area, 'area')->create(['nombre' => 'Geometría']);

        $this->actingAs($admin)
            ->get(route('Admin.asignaturas.edit', $oferta))
            ->assertOk()
            ->assertSee('En la libreta (Segundo de EGB)')
            ->assertSee('Solo asignaturas')
            ->assertSee('Solo el área (promedio)')
            ->assertSee('Área y asignaturas');
    });

    it('does not offer an area report-card mode when the catalog area has a single asignatura', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $estructura = catalogoEstructura([
            'nivel' => 'Educación General Básica',
            'subnivel' => 'Básica Elemental',
            'grado' => 'Segundo de EGB',
        ]);
        gradoEnOferta($establecimiento, $oferta, $estructura);
        catalogoAsignatura($estructura);

        $this->actingAs($admin)
            ->get(route('Admin.asignaturas.edit', $oferta))
            ->assertOk()
            ->assertDontSee('Solo el área (promedio)')
            ->assertDontSee('Área y asignaturas');
    });

    it('escapes catalog names in the malla form', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $estructura = catalogoEstructura();
        gradoEnOferta($establecimiento, $oferta, $estructura);
        catalogoAsignatura($estructura, [
            'area' => "<script>alert('area')</script>",
            'asignatura' => "<script>alert('asig')</script>",
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.asignaturas.edit', $oferta))
            ->assertSee("<script>alert('area')</script>")
            ->assertSee("<script>alert('asig')</script>")
            ->assertDontSee("<script>alert('area')</script>", false)
            ->assertDontSee("<script>alert('asig')</script>", false);
    });
});

describe('update', function () {
    it('saves the asignatura for the selected grados of that jornada', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $estructura = catalogoEstructura(['subnivel' => 'Básica Elemental']);
        gradoEnOferta($establecimiento, $oferta, $estructura);
        $asignatura = catalogoAsignatura($estructura);

        $this->actingAs($admin)
            ->put(route('Admin.asignaturas.update', $oferta), [
                'asignaturas' => [
                    $asignatura->id => [
                        'grados' => [$estructura['grado']->id],
                        'horas' => [$estructura['grado']->id => 6],
                        'libreta' => [$estructura['grado']->id => '1'],
                        'tipo_calificacion' => [$estructura['grado']->id => TipoCalificacion::Calificacion->value],
                    ],
                ],
            ])
            ->assertRedirect(route('Admin.asignaturas.edit', $oferta))
            ->assertSessionHas('status', 'asignaturas-updated');

        $this->assertDatabaseHas('establecimiento_asignaturas', [
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'grado_id' => $estructura['grado']->id,
            'asignatura_id' => $asignatura->id,
            'horas_semanales' => 6,
            'aparece_en_libreta' => true,
            'tipo_calificacion' => TipoCalificacion::Calificacion->value,
        ]);
    });

    it('inherits the subnivel grading type when none is posted', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $estructura = catalogoEstructura();
        $estructura['subnivel']->update(['tipo_calificacion' => TipoCalificacion::Destrezas]);
        gradoEnOferta($establecimiento, $oferta, $estructura);
        $asignatura = catalogoAsignatura($estructura);

        $this->actingAs($admin)
            ->put(route('Admin.asignaturas.update', $oferta), [
                'asignaturas' => [
                    $asignatura->id => [
                        'grados' => [$estructura['grado']->id],
                    ],
                ],
            ])
            ->assertRedirect(route('Admin.asignaturas.edit', $oferta));

        $this->assertDatabaseHas('establecimiento_asignaturas', [
            'asignatura_id' => $asignatura->id,
            'grado_id' => $estructura['grado']->id,
            'tipo_calificacion' => TipoCalificacion::Destrezas->value,
        ]);
    });

    it('does not save an asignatura for a jornada that did not offer that grado', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $propia = catalogoEstructura(['subnivel' => 'Básica Elemental']);
        $otra = catalogoEstructura(['nivel' => 'EGB', 'subnivel' => 'Básica Media', 'grado' => 'Quinto de EGB']);
        gradoEnOferta($establecimiento, $oferta, $propia);
        $asignatura = catalogoAsignatura($otra, ['area' => 'Matemática Media', 'asignatura' => 'Matemática Media']);

        $this->actingAs($admin)
            ->from(route('Admin.asignaturas.edit', $oferta))
            ->put(route('Admin.asignaturas.update', $oferta), [
                'asignaturas' => [
                    $asignatura->id => [
                        'grados' => [$otra['grado']->id],
                    ],
                ],
            ])
            ->assertRedirect(route('Admin.asignaturas.edit', $oferta))
            ->assertSessionHasErrors(['asignaturas' => 'Solo puedes asignar materias a los grados que esta jornada ya ofertó en Estructura.']);

        $this->assertDatabaseCount('establecimiento_asignaturas', 0);
    });

    it('rejects an asignatura whose subnivel does not match the grado', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $elemental = catalogoEstructura(['subnivel' => 'Básica Elemental']);
        $media = catalogoEstructura(['nivel' => 'EGB', 'subnivel' => 'Básica Media', 'grado' => 'Quinto de EGB']);
        gradoEnOferta($establecimiento, $oferta, $elemental);
        gradoEnOferta($establecimiento, $oferta, $media);
        $asignatura = catalogoAsignatura($elemental);

        $this->actingAs($admin)
            ->from(route('Admin.asignaturas.edit', $oferta))
            ->put(route('Admin.asignaturas.update', $oferta), [
                'asignaturas' => [
                    $asignatura->id => [
                        'grados' => [$media['grado']->id],
                    ],
                ],
            ])
            ->assertRedirect(route('Admin.asignaturas.edit', $oferta))
            ->assertSessionHasErrors(['asignaturas' => 'Cada asignatura debe pertenecer al mismo subnivel del grado seleccionado.']);

        $this->assertDatabaseCount('establecimiento_asignaturas', 0);
    });

    it('clears the malla of that jornada when nothing is selected', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $matutina = ofertaDe($establecimiento);
        $vespertina = ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
        $estructura = catalogoEstructura();
        gradoEnOferta($establecimiento, $matutina, $estructura);
        gradoEnOferta($establecimiento, $vespertina, $estructura);
        $asignatura = catalogoAsignatura($estructura);

        foreach ([$matutina, $vespertina] as $oferta) {
            EstablecimientoAsignatura::factory()->create([
                'establecimiento_id' => $establecimiento->id,
                'establecimiento_modalidad_jornada_id' => $oferta->id,
                'grado_id' => $estructura['grado']->id,
                'asignatura_id' => $asignatura->id,
            ]);
        }

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($matutina))
            ->put(route('Admin.asignaturas.update', $matutina), [])
            ->assertRedirect(route('Admin.asignaturas.edit', $matutina));

        $this->assertDatabaseMissing('establecimiento_asignaturas', [
            'establecimiento_modalidad_jornada_id' => $matutina->id,
        ]);
        $this->assertDatabaseHas('establecimiento_asignaturas', [
            'establecimiento_modalidad_jornada_id' => $vespertina->id,
            'asignatura_id' => $asignatura->id,
        ]);
    });

    it('removes malla rows when a grado is removed from the estructura of that jornada', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $estructura = catalogoEstructura();
        gradoEnOferta($establecimiento, $oferta, $estructura);
        $asignatura = catalogoAsignatura($estructura);
        EstablecimientoAsignatura::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'grado_id' => $estructura['grado']->id,
            'asignatura_id' => $asignatura->id,
        ]);

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update', $oferta), [])
            ->assertRedirect(route('Admin.estructura.edit', $oferta));

        $this->assertDatabaseMissing('establecimiento_asignaturas', [
            'establecimiento_modalidad_jornada_id' => $oferta->id,
        ]);
    });

    it('returns 404 when saving an oferta of another establishment', function () {
        $admin = adminOf(Establecimiento::factory()->create());
        $ajena = ofertaDe(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->put(route('Admin.asignaturas.update', $ajena), [])
            ->assertNotFound();
    });

    it('rejects hours greater than 40', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $estructura = catalogoEstructura();
        gradoEnOferta($establecimiento, $oferta, $estructura);
        $asignatura = catalogoAsignatura($estructura);

        $this->actingAs($admin)
            ->from(route('Admin.asignaturas.edit', $oferta))
            ->put(route('Admin.asignaturas.update', $oferta), [
                'asignaturas' => [
                    $asignatura->id => [
                        'grados' => [$estructura['grado']->id],
                        'horas' => [$estructura['grado']->id => 41],
                    ],
                ],
            ])
            ->assertRedirect(route('Admin.asignaturas.edit', $oferta))
            ->assertSessionHasErrors(['asignaturas.'.$asignatura->id.'.horas.'.$estructura['grado']->id]);

        $this->assertDatabaseCount('establecimiento_asignaturas', 0);
    });

    it('saves how the area appears on the report card when two asignaturas of that area are selected', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $estructura = catalogoEstructura(['subnivel' => 'Básica Elemental']);
        gradoEnOferta($establecimiento, $oferta, $estructura);
        $matematica = catalogoAsignatura($estructura);
        $geometria = Sys_Asignatura::factory()->for($matematica->area, 'area')->create(['nombre' => 'Geometría']);

        $this->actingAs($admin)
            ->put(route('Admin.asignaturas.update', $oferta), [
                'asignaturas' => [
                    $matematica->id => [
                        'grados' => [$estructura['grado']->id],
                    ],
                    $geometria->id => [
                        'grados' => [$estructura['grado']->id],
                    ],
                ],
                'areas' => [
                    $matematica->area_id => [
                        'grados' => [
                            $estructura['grado']->id => [
                                'modo_libreta' => ModoLibretaArea::AreaPromedio->value,
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('Admin.asignaturas.edit', $oferta));

        $this->assertDatabaseHas('establecimiento_area_libretas', [
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'grado_id' => $estructura['grado']->id,
            'area_id' => $matematica->area_id,
            'modo_libreta' => ModoLibretaArea::AreaPromedio->value,
        ]);
    });

    it('defaults the area report-card mode to asignaturas when two subjects are selected', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $estructura = catalogoEstructura(['subnivel' => 'Básica Elemental']);
        gradoEnOferta($establecimiento, $oferta, $estructura);
        $matematica = catalogoAsignatura($estructura);
        $geometria = Sys_Asignatura::factory()->for($matematica->area, 'area')->create(['nombre' => 'Geometría']);

        $this->actingAs($admin)
            ->put(route('Admin.asignaturas.update', $oferta), [
                'asignaturas' => [
                    $matematica->id => [
                        'grados' => [$estructura['grado']->id],
                    ],
                    $geometria->id => [
                        'grados' => [$estructura['grado']->id],
                    ],
                ],
            ])
            ->assertRedirect(route('Admin.asignaturas.edit', $oferta));

        $this->assertDatabaseHas('establecimiento_area_libretas', [
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'area_id' => $matematica->area_id,
            'grado_id' => $estructura['grado']->id,
            'modo_libreta' => ModoLibretaArea::Asignaturas->value,
        ]);
    });

    it('ignores a posted area report-card mode when only one asignatura of that area is selected', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $estructura = catalogoEstructura(['subnivel' => 'Básica Elemental']);
        gradoEnOferta($establecimiento, $oferta, $estructura);
        $matematica = catalogoAsignatura($estructura);
        Sys_Asignatura::factory()->for($matematica->area, 'area')->create(['nombre' => 'Geometría']);

        $this->actingAs($admin)
            ->put(route('Admin.asignaturas.update', $oferta), [
                'asignaturas' => [
                    $matematica->id => [
                        'grados' => [$estructura['grado']->id],
                    ],
                ],
                'areas' => [
                    $matematica->area_id => [
                        'grados' => [
                            $estructura['grado']->id => [
                                'modo_libreta' => ModoLibretaArea::AreaPromedio->value,
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('Admin.asignaturas.edit', $oferta));

        $this->assertDatabaseCount('establecimiento_area_libretas', 0);
    });

    it('clears the area report-card mode of that jornada when nothing is selected', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $matutina = ofertaDe($establecimiento);
        $vespertina = ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
        $estructura = catalogoEstructura();
        gradoEnOferta($establecimiento, $matutina, $estructura);
        gradoEnOferta($establecimiento, $vespertina, $estructura);
        $asignatura = catalogoAsignatura($estructura);

        foreach ([$matutina, $vespertina] as $oferta) {
            EstablecimientoAreaLibreta::factory()->create([
                'establecimiento_id' => $establecimiento->id,
                'establecimiento_modalidad_jornada_id' => $oferta->id,
                'grado_id' => $estructura['grado']->id,
                'area_id' => $asignatura->area_id,
                'modo_libreta' => ModoLibretaArea::AreaPromedio,
            ]);
        }

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($matutina))
            ->put(route('Admin.asignaturas.update', $matutina), [])
            ->assertRedirect(route('Admin.asignaturas.edit', $matutina));

        $this->assertDatabaseMissing('establecimiento_area_libretas', [
            'establecimiento_modalidad_jornada_id' => $matutina->id,
        ]);
        $this->assertDatabaseHas('establecimiento_area_libretas', [
            'establecimiento_modalidad_jornada_id' => $vespertina->id,
            'area_id' => $asignatura->area_id,
        ]);
    });

    it('removes area report-card modes when a grado is removed from the estructura of that jornada', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $estructura = catalogoEstructura();
        gradoEnOferta($establecimiento, $oferta, $estructura);
        $asignatura = catalogoAsignatura($estructura);
        EstablecimientoAreaLibreta::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'grado_id' => $estructura['grado']->id,
            'area_id' => $asignatura->area_id,
            'modo_libreta' => ModoLibretaArea::AreaYAsignaturas,
        ]);

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update', $oferta), [])
            ->assertRedirect(route('Admin.estructura.edit', $oferta));

        $this->assertDatabaseMissing('establecimiento_area_libretas', [
            'establecimiento_modalidad_jornada_id' => $oferta->id,
        ]);
    });

    it('rejects an invalid area report-card mode', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $estructura = catalogoEstructura();
        gradoEnOferta($establecimiento, $oferta, $estructura);
        $matematica = catalogoAsignatura($estructura);
        $geometria = Sys_Asignatura::factory()->for($matematica->area, 'area')->create(['nombre' => 'Geometría']);

        $this->actingAs($admin)
            ->from(route('Admin.asignaturas.edit', $oferta))
            ->put(route('Admin.asignaturas.update', $oferta), [
                'asignaturas' => [
                    $matematica->id => [
                        'grados' => [$estructura['grado']->id],
                    ],
                    $geometria->id => [
                        'grados' => [$estructura['grado']->id],
                    ],
                ],
                'areas' => [
                    $matematica->area_id => [
                        'grados' => [
                            $estructura['grado']->id => [
                                'modo_libreta' => 'inventado',
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('Admin.asignaturas.edit', $oferta))
            ->assertSessionHasErrors(['areas.'.$matematica->area_id.'.grados.'.$estructura['grado']->id.'.modo_libreta']);

        $this->assertDatabaseCount('establecimiento_area_libretas', 0);
    });

    it('forbids systems users from saving establishment asignaturas', function () {
        $oferta = ofertaDe(Establecimiento::factory()->create());
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->put(route('Admin.asignaturas.update', $oferta), [])
            ->assertForbidden();
    });
});
