<?php

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoNivel;
use App\Models\EstablecimientoSubnivel;
use App\Models\Sys_Grado;
use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use App\Models\User;
use App\Services\SyncEstablecimientoModalidades;
use Database\Seeders\NavigationSeeder;
use Illuminate\Support\Facades\DB;

/**
 * @return array{nivel: Sys_Nivel, subnivel: Sys_Subnivel, grado: Sys_Grado}
 */
function catalogoEstructura(array $nombres = []): array
{
    $nivel = Sys_Nivel::factory()->create(['nombre' => $nombres['nivel'] ?? 'Educación Inicial']);
    $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create(['nombre' => $nombres['subnivel'] ?? 'Inicial 2']);
    $grado = Sys_Grado::factory()->for($subnivel, 'subnivel')->create(['nombre' => $nombres['grado'] ?? 'Segundo de inicial']);

    return compact('nivel', 'subnivel', 'grado');
}

function ofertaDe(Establecimiento $establecimiento, array $nombres = []): EstablecimientoModalidadJornada
{
    $modalidadNombre = $nombres['modalidad'] ?? 'Presencial';
    $catalogoModalidad = Sys_Modalidad::query()->where('nombre', $modalidadNombre)->first()
        ?? Sys_Modalidad::factory()->create(['nombre' => $modalidadNombre]);

    $establecimientoModalidad = EstablecimientoModalidad::query()->firstOrCreate([
        'establecimiento_id' => $establecimiento->id,
        'modalidad_id' => $catalogoModalidad->id,
    ]);

    $jornadaNombre = $nombres['jornada'] ?? 'Matutina';
    $catalogoJornada = Sys_Jornada::query()->where('nombre', $jornadaNombre)->first()
        ?? Sys_Jornada::factory()->create(['nombre' => $jornadaNombre]);

    return EstablecimientoModalidadJornada::factory()->create([
        'establecimiento_modalidad_id' => $establecimientoModalidad->id,
        'jornada_id' => $catalogoJornada->id,
    ]);
}

describe('index', function () {
    it('lists the modalidades and jornadas of the establishment', function () {
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'Unidad Educativa Andina']);
        $admin = adminOf($establecimiento);
        $matutina = ofertaDe($establecimiento);
        $vespertina = ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
        ofertaDe($establecimiento, ['modalidad' => 'Virtual', 'jornada' => 'Nocturna']);
        ['grado' => $grado] = catalogoEstructura();
        EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $matutina->id,
            'subnivel_id' => $grado->subnivel_id,
            'grado_id' => $grado->id,
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.estructura'))
            ->assertOk()
            ->assertSee('Estructura educativa')
            ->assertSee('Unidad Educativa Andina')
            ->assertSee('Presencial')
            ->assertSee('Virtual')
            ->assertSee('Matutina')
            ->assertSee('Vespertina')
            ->assertSee('Nocturna')
            ->assertSee('1 grado')
            ->assertSee('Sin grados definidos')
            ->assertSee(route('Admin.estructura.edit', $matutina), false)
            ->assertSee(route('Admin.estructura.edit', $vespertina), false)
            ->assertSee('Definir estructura');
    });

    it('explains when the establishment has no modalidades', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->get(route('Admin.estructura'))
            ->assertOk()
            ->assertSee('Sistemas aún no ha asignado modalidades y jornadas a este establecimiento.')
            ->assertDontSee('Definir estructura');
    });

    it('does not list ofertas of another establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        ofertaDe($establecimiento, ['modalidad' => 'Presencial', 'jornada' => 'Matutina']);
        $otro = Establecimiento::factory()->create();
        $ajena = ofertaDe($otro, ['modalidad' => 'Semipresencial', 'jornada' => 'Nocturna extraña']);

        $this->actingAs($admin)
            ->get(route('Admin.estructura'))
            ->assertOk()
            ->assertSee('Matutina')
            ->assertDontSee('Semipresencial')
            ->assertDontSee('Nocturna extraña')
            ->assertDontSee(route('Admin.estructura.edit', $ajena), false);
    });

    it('escapes modalidad and jornada names on the estructura index', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        ofertaDe($establecimiento, [
            'modalidad' => "<script>alert('mod')</script>",
            'jornada' => "<script>alert('jor')</script>",
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.estructura'))
            ->assertSee("<script>alert('mod')</script>")
            ->assertSee("<script>alert('jor')</script>")
            ->assertDontSee("<script>alert('mod')</script>", false)
            ->assertDontSee("<script>alert('jor')</script>", false);
    });

    it('forbids systems users from opening establishment estructura', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->get(route('Admin.estructura'))
            ->assertForbidden();
    });

    it('redirects guests from establishment estructura to login', function () {
        $this->get(route('Admin.estructura'))
            ->assertRedirect(route('login'));
    });

    it('forbids administrators without an establishment', function () {
        $admin = assignRole(User::factory()->create(['establecimiento_id' => null]), Role::Admin);

        $this->actingAs($admin)
            ->get(route('Admin.estructura'))
            ->assertForbidden();
    });

    it('shows the estructura option to administrators', function () {
        $this->seed(NavigationSeeder::class);
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertSee('Estructura')
            ->assertSee(route('Admin.estructura'), false);
    });
});

describe('edit', function () {
    it('allows administrators to see the national catalog of a jornada', function () {
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'Unidad Educativa Andina']);
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        catalogoEstructura();
        catalogoEstructura([
            'nivel' => 'Bachillerato',
            'subnivel' => 'Bachillerato General Unificado',
            'grado' => 'Primero de BGU',
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.estructura.edit', $oferta))
            ->assertOk()
            ->assertSee('Estructura educativa')
            ->assertSee('Unidad Educativa Andina')
            ->assertSee('Presencial')
            ->assertSee('Matutina')
            ->assertSee('Catálogo nacional')
            ->assertSee('Nivel: Educación Inicial')
            ->assertSee('Bachillerato')
            ->assertSee('Subnivel: Inicial 2')
            ->assertSee('Grados')
            ->assertSee('Segundo de inicial')
            ->assertSee('name="grados[]"', false)
            ->assertSee('name="nombre_grados[', false)
            ->assertSee('name="nombre_subniveles[', false)
            ->assertSee('aria-label="Nombre en el establecimiento de Inicial 2"', false)
            ->assertSee('aria-label="Nombre en el establecimiento de Segundo de inicial"', false)
            ->assertSee('Nombre en el establecimiento')
            ->assertDontSee('name="niveles[]"', false)
            ->assertDontSee('name="subniveles[]"', false)
            ->assertSee('Guardar estructura')
            ->assertSee(route('Admin.estructura'), false)
            ->assertSee(route('Admin.estructura.update', $oferta), false);
    });

    it('shows previously selected items for that jornada', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        ['subnivel' => $subnivel, 'grado' => $grado] = catalogoEstructura();
        EstablecimientoSubnivel::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'nivel_id' => $subnivel->nivel_id,
            'subnivel_id' => $subnivel->id,
            'nombre' => 'Prekínder institucional',
        ]);
        EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $grado->subnivel_id,
            'grado_id' => $grado->id,
            'nombre' => 'Kínder',
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.estructura.edit', $oferta))
            ->assertOk()
            ->assertViewHas('selectedGradoIds', [(string) $grado->id])
            ->assertSee('Kínder')
            ->assertSee('Prekínder institucional');
    });

    it('does not mark another jornada selection as checked', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $matutina = ofertaDe($establecimiento);
        $vespertina = ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
        ['grado' => $grado] = catalogoEstructura();
        EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $vespertina->id,
            'subnivel_id' => $grado->subnivel_id,
            'grado_id' => $grado->id,
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.estructura.edit', $matutina))
            ->assertOk()
            ->assertViewHas('selectedGradoIds', []);
    });

    it('does not mark another establishment selection as checked', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $otro = Establecimiento::factory()->create();
        $ajena = ofertaDe($otro);
        ['grado' => $grado] = catalogoEstructura();
        EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $otro->id,
            'establecimiento_modalidad_jornada_id' => $ajena->id,
            'subnivel_id' => $grado->subnivel_id,
            'grado_id' => $grado->id,
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.estructura.edit', $oferta))
            ->assertOk()
            ->assertViewHas('selectedGradoIds', []);
    });

    it('returns 404 when editing an oferta of another establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $ajena = ofertaDe(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('Admin.estructura.edit', $ajena))
            ->assertNotFound();
    });

    it('escapes catalog names in the estructura form', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        catalogoEstructura([
            'nivel' => "<script>alert('xss')</script>",
            'subnivel' => 'Inicial 2',
            'grado' => 'Segundo de inicial',
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.estructura.edit', $oferta))
            ->assertSee("<script>alert('xss')</script>")
            ->assertDontSee("<script>alert('xss')</script>", false);
    });

    it('escapes custom establishment names in the estructura form', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        ['subnivel' => $subnivel, 'grado' => $grado] = catalogoEstructura();
        EstablecimientoSubnivel::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'nivel_id' => $subnivel->nivel_id,
            'subnivel_id' => $subnivel->id,
            'nombre' => "<script>alert('sub')</script>",
        ]);
        EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $grado->subnivel_id,
            'grado_id' => $grado->id,
            'nombre' => "<script>alert('grado')</script>",
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.estructura.edit', $oferta))
            ->assertSee("<script>alert('sub')</script>")
            ->assertSee("<script>alert('grado')</script>")
            ->assertDontSee("<script>alert('sub')</script>", false)
            ->assertDontSee("<script>alert('grado')</script>", false);
    });
});

describe('update', function () {
    it('saves the nivel and subnivel that belong to each selected grado of that jornada', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        ['nivel' => $nivel, 'subnivel' => $subnivel, 'grado' => $grado] = catalogoEstructura();
        $otroNivel = Sys_Nivel::factory()->create(['nombre' => 'Bachillerato']);

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update', $oferta), [
                'grados' => [$grado->id],
            ])
            ->assertRedirect(route('Admin.estructura.edit', $oferta))
            ->assertSessionHas('status', 'estructura-updated');

        $this->assertDatabaseHas('establecimiento_niveles', [
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'nivel_id' => $nivel->id,
        ]);
        $this->assertDatabaseHas('establecimiento_subniveles', [
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'nivel_id' => $nivel->id,
            'subnivel_id' => $subnivel->id,
        ]);
        $this->assertDatabaseHas('establecimiento_grados', [
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $subnivel->id,
            'grado_id' => $grado->id,
            'nombre' => null,
        ]);
        $this->assertDatabaseMissing('establecimiento_niveles', [
            'establecimiento_id' => $establecimiento->id,
            'nivel_id' => $otroNivel->id,
        ]);
    });

    it('saves custom names for the selected grado and its subnivel', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        ['subnivel' => $subnivel, 'grado' => $grado] = catalogoEstructura();

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update', $oferta), [
                'grados' => [$grado->id],
                'nombre_grados' => [$grado->id => '  Kínder  '],
                'nombre_subniveles' => [$subnivel->id => 'Prekínder institucional'],
            ])
            ->assertRedirect(route('Admin.estructura.edit', $oferta))
            ->assertSessionHas('status', 'estructura-updated');

        $this->assertDatabaseHas('establecimiento_grados', [
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'grado_id' => $grado->id,
            'nombre' => 'Kínder',
        ]);
        $this->assertDatabaseHas('establecimiento_subniveles', [
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $subnivel->id,
            'nombre' => 'Prekínder institucional',
        ]);
    });

    it('ignores custom names of grados that were not selected', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $inicial = catalogoEstructura(['grado' => 'Inicial 2']);
        $egb = catalogoEstructura([
            'nivel' => 'Educación General Básica',
            'subnivel' => 'Preparatoria',
            'grado' => 'Primero de EGB',
        ]);

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update', $oferta), [
                'grados' => [$inicial['grado']->id],
                'nombre_grados' => [
                    $inicial['grado']->id => 'Kínder',
                    $egb['grado']->id => 'Primero institucional',
                ],
                'nombre_subniveles' => [
                    $inicial['subnivel']->id => 'Inicial propio',
                    $egb['subnivel']->id => 'Preparatoria propia',
                ],
            ])
            ->assertRedirect(route('Admin.estructura.edit', $oferta));

        $this->assertDatabaseHas('establecimiento_grados', [
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'grado_id' => $inicial['grado']->id,
            'nombre' => 'Kínder',
        ]);
        $this->assertDatabaseMissing('establecimiento_grados', [
            'establecimiento_id' => $establecimiento->id,
            'grado_id' => $egb['grado']->id,
        ]);
        $this->assertDatabaseMissing('establecimiento_subniveles', [
            'establecimiento_id' => $establecimiento->id,
            'subnivel_id' => $egb['subnivel']->id,
        ]);
    });

    it('stores a blank custom name as null', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        ['subnivel' => $subnivel, 'grado' => $grado] = catalogoEstructura();

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update', $oferta), [
                'grados' => [$grado->id],
                'nombre_grados' => [$grado->id => '   '],
                'nombre_subniveles' => [$subnivel->id => ''],
            ])
            ->assertRedirect(route('Admin.estructura.edit', $oferta));

        $this->assertDatabaseHas('establecimiento_grados', [
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'grado_id' => $grado->id,
            'nombre' => null,
        ]);
        $this->assertDatabaseHas('establecimiento_subniveles', [
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $subnivel->id,
            'nombre' => null,
        ]);
    });

    it('rejects a custom name that is longer than 150 characters', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        ['grado' => $grado] = catalogoEstructura();

        $this->actingAs($admin)
            ->from(route('Admin.estructura.edit', $oferta))
            ->put(route('Admin.estructura.update', $oferta), [
                'grados' => [$grado->id],
                'nombre_grados' => [$grado->id => str_repeat('a', 151)],
            ])
            ->assertRedirect(route('Admin.estructura.edit', $oferta))
            ->assertSessionHasErrors(['nombre_grados.'.$grado->id => 'El nombre del grado en el establecimiento no puede tener más de 150 caracteres.']);

        $this->assertDatabaseCount('establecimiento_grados', 0);
    });

    it('ignores submitted niveles that do not belong to a selected grado', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        ['nivel' => $nivel, 'grado' => $grado] = catalogoEstructura();
        $otroNivel = Sys_Nivel::factory()->create(['nombre' => 'Bachillerato']);

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update', $oferta), [
                'niveles' => [$otroNivel->id],
                'subniveles' => [999],
                'grados' => [$grado->id],
            ])
            ->assertRedirect(route('Admin.estructura.edit', $oferta))
            ->assertSessionHas('status', 'estructura-updated');

        $this->assertDatabaseHas('establecimiento_niveles', [
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'nivel_id' => $nivel->id,
        ]);
        $this->assertDatabaseMissing('establecimiento_niveles', [
            'establecimiento_id' => $establecimiento->id,
            'nivel_id' => $otroNivel->id,
        ]);
    });

    it('clears only the structure of that jornada when nothing is selected', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $matutina = ofertaDe($establecimiento);
        $vespertina = ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
        ['nivel' => $nivel, 'subnivel' => $subnivel, 'grado' => $grado] = catalogoEstructura();
        foreach ([$matutina, $vespertina] as $oferta) {
            EstablecimientoNivel::factory()->create([
                'establecimiento_id' => $establecimiento->id,
                'establecimiento_modalidad_jornada_id' => $oferta->id,
                'nivel_id' => $nivel->id,
            ]);
            EstablecimientoSubnivel::factory()->create([
                'establecimiento_id' => $establecimiento->id,
                'establecimiento_modalidad_jornada_id' => $oferta->id,
                'nivel_id' => $nivel->id,
                'subnivel_id' => $subnivel->id,
            ]);
            EstablecimientoGrado::factory()->create([
                'establecimiento_id' => $establecimiento->id,
                'establecimiento_modalidad_jornada_id' => $oferta->id,
                'subnivel_id' => $subnivel->id,
                'grado_id' => $grado->id,
            ]);
        }

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update', $matutina), [])
            ->assertRedirect(route('Admin.estructura.edit', $matutina))
            ->assertSessionHas('status', 'estructura-updated');

        $this->assertDatabaseMissing('establecimiento_niveles', [
            'establecimiento_modalidad_jornada_id' => $matutina->id,
        ]);
        $this->assertDatabaseMissing('establecimiento_subniveles', [
            'establecimiento_modalidad_jornada_id' => $matutina->id,
        ]);
        $this->assertDatabaseMissing('establecimiento_grados', [
            'establecimiento_modalidad_jornada_id' => $matutina->id,
        ]);
        $this->assertDatabaseHas('establecimiento_niveles', [
            'establecimiento_modalidad_jornada_id' => $vespertina->id,
            'nivel_id' => $nivel->id,
        ]);
        $this->assertDatabaseHas('establecimiento_grados', [
            'establecimiento_modalidad_jornada_id' => $vespertina->id,
            'grado_id' => $grado->id,
        ]);
    });

    it('replaces a previous selection for the same jornada', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $inicial = catalogoEstructura();
        $bachillerato = catalogoEstructura([
            'nivel' => 'Bachillerato',
            'subnivel' => 'Bachillerato General Unificado',
            'grado' => 'Primero de BGU',
        ]);
        EstablecimientoNivel::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'nivel_id' => $inicial['nivel']->id,
        ]);

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update', $oferta), [
                'grados' => [$bachillerato['grado']->id],
            ])
            ->assertRedirect(route('Admin.estructura.edit', $oferta));

        $this->assertDatabaseMissing('establecimiento_niveles', [
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'nivel_id' => $inicial['nivel']->id,
        ]);
        $this->assertDatabaseHas('establecimiento_niveles', [
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'nivel_id' => $bachillerato['nivel']->id,
        ]);
        $this->assertDatabaseHas('establecimiento_subniveles', [
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $bachillerato['subnivel']->id,
        ]);
        $this->assertDatabaseHas('establecimiento_grados', [
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'grado_id' => $bachillerato['grado']->id,
        ]);
    });

    it('does not change the structure of another jornada or establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $matutina = ofertaDe($establecimiento);
        $vespertina = ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
        $otro = Establecimiento::factory()->create();
        $ajena = ofertaDe($otro);
        $propia = catalogoEstructura(['nivel' => 'Educación Inicial']);
        $ajenaCatalogo = catalogoEstructura([
            'nivel' => 'Educación General Básica',
            'subnivel' => 'Elemental',
            'grado' => 'Segundo de EGB',
        ]);
        foreach ([$vespertina, $ajena] as $oferta) {
            EstablecimientoNivel::factory()->create([
                'establecimiento_id' => $oferta === $ajena ? $otro->id : $establecimiento->id,
                'establecimiento_modalidad_jornada_id' => $oferta->id,
                'nivel_id' => $ajenaCatalogo['nivel']->id,
            ]);
            EstablecimientoSubnivel::factory()->create([
                'establecimiento_id' => $oferta === $ajena ? $otro->id : $establecimiento->id,
                'establecimiento_modalidad_jornada_id' => $oferta->id,
                'nivel_id' => $ajenaCatalogo['nivel']->id,
                'subnivel_id' => $ajenaCatalogo['subnivel']->id,
            ]);
            EstablecimientoGrado::factory()->create([
                'establecimiento_id' => $oferta === $ajena ? $otro->id : $establecimiento->id,
                'establecimiento_modalidad_jornada_id' => $oferta->id,
                'subnivel_id' => $ajenaCatalogo['subnivel']->id,
                'grado_id' => $ajenaCatalogo['grado']->id,
            ]);
        }

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update', $matutina), [
                'grados' => [$propia['grado']->id],
            ])
            ->assertRedirect(route('Admin.estructura.edit', $matutina));

        $this->assertDatabaseHas('establecimiento_grados', [
            'establecimiento_modalidad_jornada_id' => $vespertina->id,
            'grado_id' => $ajenaCatalogo['grado']->id,
        ]);
        $this->assertDatabaseHas('establecimiento_grados', [
            'establecimiento_modalidad_jornada_id' => $ajena->id,
            'grado_id' => $ajenaCatalogo['grado']->id,
        ]);
        $this->assertDatabaseMissing('establecimiento_niveles', [
            'establecimiento_id' => $otro->id,
            'nivel_id' => $propia['nivel']->id,
        ]);
    });

    it('returns 404 when saving an oferta of another establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $ajena = ofertaDe(Establecimiento::factory()->create());
        ['grado' => $grado] = catalogoEstructura();

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update', $ajena), [
                'grados' => [$grado->id],
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('establecimiento_grados', 0);
    });

    it('rejects a grado that has no subnivel in the catalog', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $grado = Sys_Grado::factory()->create([
            'nombre' => 'Sin subnivel',
            'subnivel_id' => null,
        ]);

        $this->actingAs($admin)
            ->from(route('Admin.estructura.edit', $oferta))
            ->put(route('Admin.estructura.update', $oferta), [
                'grados' => [$grado->id],
            ])
            ->assertRedirect(route('Admin.estructura.edit', $oferta))
            ->assertSessionHasErrors(['grados' => 'Cada grado debe pertenecer a un subnivel y un nivel del catálogo.']);

        $this->assertDatabaseCount('establecimiento_grados', 0);
        $this->assertDatabaseCount('establecimiento_niveles', 0);
    });

    it('rejects catalog ids that do not exist', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);

        $this->actingAs($admin)
            ->from(route('Admin.estructura.edit', $oferta))
            ->put(route('Admin.estructura.update', $oferta), [
                'grados' => [999],
            ])
            ->assertRedirect(route('Admin.estructura.edit', $oferta))
            ->assertSessionHasErrors(['grados.0' => 'El grado seleccionado no existe en el catálogo de sistemas.']);

        $this->assertDatabaseCount('establecimiento_grados', 0);
    });

    it('assigns pending estructura to newly created jornadas', function () {
        $establecimiento = Establecimiento::factory()->create();
        ['nivel' => $nivel, 'subnivel' => $subnivel, 'grado' => $grado] = catalogoEstructura();
        $now = now();

        DB::table('establecimiento_niveles')->insert([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => null,
            'nivel_id' => $nivel->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('establecimiento_subniveles')->insert([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => null,
            'nivel_id' => $nivel->id,
            'subnivel_id' => $subnivel->id,
            'nombre' => 'Prekínder institucional',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('establecimiento_grados')->insert([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => null,
            'subnivel_id' => $subnivel->id,
            'grado_id' => $grado->id,
            'nombre' => 'Kínder',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $modalidad = Sys_Modalidad::factory()->create();
        $jornada = Sys_Jornada::factory()->create();

        app(SyncEstablecimientoModalidades::class)->handle($establecimiento, [
            $modalidad->id => [$jornada->id],
        ]);

        $oferta = EstablecimientoModalidadJornada::query()
            ->where('jornada_id', $jornada->id)
            ->firstOrFail();

        $this->assertDatabaseHas('establecimiento_grados', [
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'grado_id' => $grado->id,
            'nombre' => 'Kínder',
        ]);
        $this->assertDatabaseHas('establecimiento_subniveles', [
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $subnivel->id,
            'nombre' => 'Prekínder institucional',
        ]);
        $this->assertDatabaseMissing('establecimiento_grados', [
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => null,
        ]);
    });

    it('keeps the estructura of a jornada when the same modalidad and jornada remain', function () {
        $establecimiento = Establecimiento::factory()->create();
        $oferta = ofertaDe($establecimiento);
        ['grado' => $grado] = catalogoEstructura();
        EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $grado->subnivel_id,
            'grado_id' => $grado->id,
        ]);

        app(SyncEstablecimientoModalidades::class)->handle($establecimiento, [
            $oferta->establecimientoModalidad->modalidad_id => [$oferta->jornada_id],
        ]);

        $this->assertDatabaseHas('establecimiento_grados', [
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'grado_id' => $grado->id,
        ]);
    });

    it('forbids systems users from saving establishment estructura', function () {
        $oferta = ofertaDe(Establecimiento::factory()->create());
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->put(route('Admin.estructura.update', $oferta), [])
            ->assertForbidden();
    });

    it('forbids administrators without an establishment from saving estructura', function () {
        $oferta = ofertaDe(Establecimiento::factory()->create());
        $admin = assignRole(User::factory()->create(['establecimiento_id' => null]), Role::Admin);

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update', $oferta), [])
            ->assertForbidden();
    });
});
