<?php

use App\Enums\Role;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\ImportData;
use App\Models\Padre;
use App\Models\Persona;
use App\Models\Sys_Pais;
use App\Models\Sys_Provincia;
use App\Models\Sys_TipoContrato;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

/**
 * @param  list<list<string>>  $rows
 */
function representantesCsv(array $rows, string $name = 'representantes.csv'): UploadedFile
{
    $lines = [];

    foreach ($rows as $row) {
        $lines[] = implode(',', array_map(
            fn (string $value): string => '"'.str_replace('"', '""', $value).'"',
            $row,
        ));
    }

    return UploadedFile::fake()->createWithContent($name, implode("\n", $lines));
}

/**
 * @param  list<list<string>>  $rows
 */
function representantesXlsx(array $rows, string $name = 'representantes.xlsx'): UploadedFile
{
    $unique = [];
    $sheetRows = '';
    $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

    foreach ($rows as $rowIndex => $cells) {
        $r = $rowIndex + 1;
        $cellsXml = '';

        foreach ($cells as $column => $value) {
            if (! isset($unique[$value])) {
                $unique[$value] = count($unique);
            }

            $cellsXml .= '<c r="'.$letters[$column].$r.'" t="s"><v>'.$unique[$value].'</v></c>';
        }

        $sheetRows .= '<row r="'.$r.'">'.$cellsXml.'</row>';
    }

    $shared = '';

    foreach (array_keys($unique) as $value) {
        $shared .= '<si><t>'.htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t></si>';
    }

    $count = count($unique);

    return UploadedFile::fake()->createWithContent($name, deflatedZip([
        '[Content_Types].xml' => <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
</Types>
XML,
        '_rels/.rels' => <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML,
        'xl/workbook.xml' => <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Hoja1" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML,
        'xl/_rels/workbook.xml.rels' => <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>
XML,
        'xl/sharedStrings.xml' => '<?xml version="1.0" encoding="UTF-8"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.$count.'" uniqueCount="'.$count.'">'.$shared.'</sst>',
        'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$sheetRows.'</sheetData></worksheet>',
    ]));
}

/**
 * @param  array<string, string>  $files
 */
function deflatedZip(array $files): string
{
    $locals = '';
    $central = '';
    $offset = 0;
    $count = 0;

    foreach ($files as $filename => $contents) {
        $crc = crc32($contents);
        $uncompressed = strlen($contents);
        $payload = gzdeflate($contents, 9);

        if (! is_string($payload)) {
            $payload = $contents;
            $method = 0;
        } else {
            $method = 8;
        }

        $compressed = strlen($payload);
        $local = pack(
            'VvvvvvVVVvv',
            0x04034B50,
            20,
            0,
            $method,
            0,
            0,
            $crc,
            $compressed,
            $uncompressed,
            strlen($filename),
            0,
        ).$filename.$payload;

        $central .= pack(
            'VvvvvvvVVVvvvvvVV',
            0x02014B50,
            20,
            20,
            0,
            $method,
            0,
            0,
            $crc,
            $compressed,
            $uncompressed,
            strlen($filename),
            0,
            0,
            0,
            0,
            0,
            $offset,
        ).$filename;

        $offset += strlen($local);
        $locals .= $local;
        $count++;
    }

    return $locals.$central.pack(
        'VvvvvVVv',
        0x06054B50,
        0,
        0,
        $count,
        $count,
        strlen($central),
        $offset,
        0,
    );
}

function representantesHeader(): array
{
    return ['tipo_identificacion', 'identificacion', 'email', 'nombres', 'apellidos'];
}

function alumnosHeader(): array
{
    return [...representantesHeader(), 'identificacion_representante'];
}

/**
 * @return array{tipo: string, archivo: UploadedFile}
 */
function importPayload(UploadedFile $archivo, string $tipo = 'padres'): array
{
    return [
        'tipo' => $tipo,
        'archivo' => $archivo,
    ];
}

describe('create', function () {
    it('allows administrators to open the import form', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('Admin.padres.import'))
            ->assertOk()
            ->assertSee('Importar padres, alumnos o docentes')
            ->assertSee('Padres')
            ->assertSee('Alumnos')
            ->assertSee('Docentes')
            ->assertSee('tipo_identificacion')
            ->assertSee('identificacion_representante');
    });

    it('preselects alumnos when the form is opened for alumnos', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('Admin.padres.import', ['tipo' => 'alumnos']))
            ->assertOk()
            ->assertSee('value="alumnos"', false)
            ->assertSee('checked', false);
    });

    it('preselects docentes when the form is opened for docentes', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('Admin.padres.import', ['tipo' => 'docentes']))
            ->assertOk()
            ->assertSee('value="docentes"', false)
            ->assertSee('checked', false);
    });

    it('forbids systems users from opening the import form', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->get(route('Admin.padres.import'))
            ->assertForbidden();
    });

    it('redirects guests from the import form to login', function () {
        $this->get(route('Admin.padres.import'))
            ->assertRedirect(route('login'));
    });

    it('forbids administrators without an establishment', function () {
        $admin = assignRole(User::factory()->create(['establecimiento_id' => null]), Role::Admin);

        $this->actingAs($admin)
            ->get(route('Admin.padres.import'))
            ->assertForbidden();
    });
});

describe('store', function () {
    it('imports representantes from a spreadsheet for the logged-in establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        Sys_Provincia::factory()->create(['pais_id' => $pais->id]);

        $response = $this->actingAs($admin)
            ->post(route('Admin.padres.import.store'), importPayload(representantesCsv([
                representantesHeader(),
                ['C', '0911111111', 'ana.mora@example.com', 'Ana', 'Mora'],
                ['C', '0922222222', 'luis.vera@example.com', 'Luis', 'Vera'],
            ])));

        $import = ImportData::query()->firstOrFail();

        $response->assertRedirect(route('Admin.padres.import.show', $import))
            ->assertSessionHas('status', 'import-processed');

        expect($import->establecimiento_id)->toBe($establecimiento->id)
            ->and($import->usuario)->toBe('Director Andino')
            ->and($import->tablas)->toBe('users,personas,padres')
            ->and($import->tipo_archivo)->toBe('csv')
            ->and($import->mensaje)->toBe('Importados: 2. Fallidos: 0.');

        $ana = User::query()->where('email', 'ana.mora@example.com')->firstOrFail();
        $persona = Persona::query()->where('identificacion', '0911111111')->firstOrFail();

        expect($ana->hasRole(Role::Padre))->toBeTrue()
            ->and($ana->establecimiento_id)->toBe($establecimiento->id)
            ->and(Hash::check('0911111111', $ana->password))->toBeTrue()
            ->and($persona->nombres)->toBe('Ana')
            ->and($persona->apellidos)->toBe('Mora')
            ->and($persona->tipo_identificacion_id)->toBe(1)
            ->and($persona->establecimiento_id)->toBe($establecimiento->id)
            ->and($persona->usuario)->toBe('Director Andino');

        expect(Padre::query()->where('persona_id', $persona->id)->exists())->toBeTrue();
        $this->assertDatabaseHas('import_data_detalles', [
            'import_data_id' => $import->id,
            'num_fila' => 2,
            'identificacion' => '0911111111',
            'descripcion' => 'Importado correctamente',
        ]);
        $this->assertDatabaseHas('import_data_detalles', [
            'import_data_id' => $import->id,
            'num_fila' => 3,
            'identificacion' => '0922222222',
            'descripcion' => 'Importado correctamente',
        ]);
    });

    it('imports representantes from an excel file', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        Sys_Provincia::factory()->create(['pais_id' => $pais->id]);

        $this->actingAs($admin)
            ->post(route('Admin.padres.import.store'), importPayload(representantesXlsx([
                representantesHeader(),
                ['C', '0944444444', 'sofia.nunez@example.com', 'Sofía', 'Núñez'],
            ])));

        $import = ImportData::query()->firstOrFail();

        expect($import->tipo_archivo)->toBe('xlsx')
            ->and($import->mensaje)->toBe('Importados: 1. Fallidos: 0.');
        $this->assertDatabaseHas('users', ['email' => 'sofia.nunez@example.com']);
        $this->assertDatabaseHas('personas', ['identificacion' => '0944444444']);
    });

    it('records failed rows without creating representantes', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        User::factory()->create(['email' => 'ya.existe@example.com']);

        $this->actingAs($admin)
            ->post(route('Admin.padres.import.store'), importPayload(representantesCsv([
                representantesHeader(),
                ['C', '0911111111', 'ana.mora@example.com', 'Ana', 'Mora'],
                ['X', '0922222222', 'malo@example.com', 'Luis', 'Vera'],
                ['C', '0933333333', 'ya.existe@example.com', 'Eva', 'Sol'],
                ['C', '', 'sin.id@example.com', 'Pia', 'Rios'],
            ])));

        $import = ImportData::query()->firstOrFail();

        expect($import->mensaje)->toBe('Importados: 1. Fallidos: 3.')
            ->and(User::query()->where('email', 'ana.mora@example.com')->exists())->toBeTrue()
            ->and(User::query()->where('email', 'malo@example.com')->exists())->toBeFalse()
            ->and(User::query()->where('email', 'sin.id@example.com')->exists())->toBeFalse();

        $this->assertDatabaseHas('import_data_detalles', [
            'import_data_id' => $import->id,
            'identificacion' => '0922222222',
            'descripcion' => 'Tipo de identificación no válido. Usa C (cédula), P (pasaporte) o R (RUC).',
        ]);
        $this->assertDatabaseHas('import_data_detalles', [
            'import_data_id' => $import->id,
            'identificacion' => '0933333333',
            'descripcion' => 'El correo ya está registrado.',
        ]);
        $this->assertDatabaseHas('import_data_detalles', [
            'import_data_id' => $import->id,
            'descripcion' => 'Falta identificacion.',
        ]);
    });

    it('does not import a duplicate identification already in the database', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        Persona::factory()->create([
            'identificacion' => '0911111111',
            'nacionalidad_id' => $pais->id,
            'provincia_id' => $provincia->id,
        ]);

        $this->actingAs($admin)
            ->post(route('Admin.padres.import.store'), importPayload(representantesCsv([
                representantesHeader(),
                ['C', '0911111111', 'ana.mora@example.com', 'Ana', 'Mora'],
            ])));

        $this->assertDatabaseMissing('users', ['email' => 'ana.mora@example.com']);
        $this->assertDatabaseHas('import_data_detalles', [
            'identificacion' => '0911111111',
            'descripcion' => 'La identificación ya está registrada.',
        ]);
    });

    it('does not create records when required columns are missing', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        Sys_Provincia::factory()->create(['pais_id' => $pais->id]);

        $this->actingAs($admin)
            ->post(route('Admin.padres.import.store'), importPayload(representantesCsv([
                ['identificacion', 'nombres'],
                ['0911111111', 'Ana'],
            ])));

        $import = ImportData::query()->firstOrFail();

        expect($import->mensaje)->toBe('Importados: 0. Fallidos: 1.');
        $this->assertDatabaseHas('import_data_detalles', [
            'import_data_id' => $import->id,
            'descripcion' => 'Faltan columnas: tipo_identificacion, email, apellidos.',
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'ana.mora@example.com']);
    });

    it('does not process the file when it is missing', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->from(route('Admin.padres.import'))
            ->post(route('Admin.padres.import.store'), ['tipo' => 'padres'])
            ->assertRedirect(route('Admin.padres.import'))
            ->assertSessionHasErrors('archivo');

        expect(ImportData::query()->exists())->toBeFalse();
    });

    it('forbids systems users from importing representantes', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->post(route('Admin.padres.import.store'), importPayload(representantesCsv([representantesHeader()])))
            ->assertForbidden();
    });

    it('does not process the file when the import type is missing', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->from(route('Admin.padres.import'))
            ->post(route('Admin.padres.import.store'), [
                'archivo' => representantesCsv([representantesHeader()]),
            ])
            ->assertRedirect(route('Admin.padres.import'))
            ->assertSessionHasErrors('tipo');

        expect(ImportData::query()->exists())->toBeFalse();
    });

    it('imports alumnos linked to a representante of the same establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $padrePersona = Persona::factory()->create([
            'identificacion' => '0911111111',
            'establecimiento_id' => $establecimiento->id,
            'nacionalidad_id' => $pais->id,
            'provincia_id' => $provincia->id,
        ]);
        $padre = Padre::factory()->for($padrePersona)->create();

        $response = $this->actingAs($admin)
            ->post(route('Admin.padres.import.store'), importPayload(representantesCsv([
                alumnosHeader(),
                ['C', '0955555555', 'mateo.nunez@example.com', 'Mateo', 'Núñez', '0911111111'],
            ]), 'alumnos'));

        $import = ImportData::query()->firstOrFail();

        $response->assertRedirect(route('Admin.padres.import.show', $import))
            ->assertSessionHas('status', 'import-processed');

        $alumnoUser = User::query()->where('email', 'mateo.nunez@example.com')->firstOrFail();
        $persona = Persona::query()->where('identificacion', '0955555555')->firstOrFail();
        $alumno = Alumno::query()->where('persona_id', $persona->id)->firstOrFail();

        expect($import->tablas)->toBe('users,personas,alumnos')
            ->and($import->mensaje)->toBe('Importados: 1. Fallidos: 0.')
            ->and($alumnoUser->hasRole(Role::Alumno))->toBeTrue()
            ->and($alumnoUser->establecimiento_id)->toBe($establecimiento->id)
            ->and(Hash::check('0955555555', $alumnoUser->password))->toBeTrue()
            ->and($persona->nombres)->toBe('Mateo')
            ->and($persona->establecimiento_id)->toBe($establecimiento->id)
            ->and($alumno->padre_id)->toBe($padre->id);

        $this->assertDatabaseHas('import_data_detalles', [
            'import_data_id' => $import->id,
            'identificacion' => '0955555555',
            'descripcion' => 'Importado correctamente',
        ]);
    });

    it('imports alumnos from an excel file', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $padrePersona = Persona::factory()->create([
            'identificacion' => '0911111111',
            'establecimiento_id' => $establecimiento->id,
            'nacionalidad_id' => $pais->id,
            'provincia_id' => $provincia->id,
        ]);
        Padre::factory()->for($padrePersona)->create();

        $this->actingAs($admin)
            ->post(route('Admin.padres.import.store'), importPayload(representantesXlsx([
                alumnosHeader(),
                ['C', '0966666666', 'luna.vera@example.com', 'Luna', 'Vera', '0911111111'],
            ]), 'alumnos'));

        $import = ImportData::query()->firstOrFail();
        $persona = Persona::query()->where('identificacion', '0966666666')->firstOrFail();

        expect($import->tipo_archivo)->toBe('xlsx')
            ->and($import->mensaje)->toBe('Importados: 1. Fallidos: 0.');
        $this->assertDatabaseHas('users', ['email' => 'luna.vera@example.com']);
        expect(Alumno::query()->where('persona_id', $persona->id)->exists())->toBeTrue();
    });

    it('does not import an alumno when the representante is missing', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        Sys_Provincia::factory()->create(['pais_id' => $pais->id]);

        $this->actingAs($admin)
            ->post(route('Admin.padres.import.store'), importPayload(representantesCsv([
                alumnosHeader(),
                ['C', '0955555555', 'mateo.nunez@example.com', 'Mateo', 'Núñez', '0911111111'],
            ]), 'alumnos'));

        $this->assertDatabaseMissing('users', ['email' => 'mateo.nunez@example.com']);
        $this->assertDatabaseHas('import_data_detalles', [
            'identificacion' => '0955555555',
            'descripcion' => 'No se encontró el representante con esa identificación en este instituto.',
        ]);
    });

    it('does not import an alumno when the representante belongs to another establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $otherPersona = Persona::factory()->create([
            'identificacion' => '0911111111',
            'nacionalidad_id' => $pais->id,
            'provincia_id' => $provincia->id,
        ]);
        Padre::factory()->for($otherPersona)->create();

        $this->actingAs($admin)
            ->post(route('Admin.padres.import.store'), importPayload(representantesCsv([
                alumnosHeader(),
                ['C', '0955555555', 'mateo.nunez@example.com', 'Mateo', 'Núñez', '0911111111'],
            ]), 'alumnos'));

        $this->assertDatabaseMissing('users', ['email' => 'mateo.nunez@example.com']);
        $this->assertDatabaseHas('import_data_detalles', [
            'identificacion' => '0955555555',
            'descripcion' => 'No se encontró el representante con esa identificación en este instituto.',
        ]);
    });

    it('does not create alumnos when identificacion_representante is missing from the file', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        Sys_Provincia::factory()->create(['pais_id' => $pais->id]);

        $this->actingAs($admin)
            ->post(route('Admin.padres.import.store'), importPayload(representantesCsv([
                representantesHeader(),
                ['C', '0955555555', 'mateo.nunez@example.com', 'Mateo', 'Núñez'],
            ]), 'alumnos'));

        $import = ImportData::query()->firstOrFail();

        expect($import->mensaje)->toBe('Importados: 0. Fallidos: 1.');
        $this->assertDatabaseHas('import_data_detalles', [
            'import_data_id' => $import->id,
            'descripcion' => 'Faltan columnas: identificacion_representante.',
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'mateo.nunez@example.com']);
    });

    it('imports docentes as employees with the docente role', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $tipoContrato = Sys_TipoContrato::factory()->create();

        $response = $this->actingAs($admin)
            ->post(route('Admin.padres.import.store'), importPayload(representantesCsv([
                representantesHeader(),
                ['C', '0977777777', 'carla.rios@example.com', 'Carla', 'Ríos'],
            ]), 'docentes'));

        $import = ImportData::query()->firstOrFail();

        $response->assertRedirect(route('Admin.padres.import.show', $import))
            ->assertSessionHas('status', 'import-processed');

        $user = User::query()->where('email', 'carla.rios@example.com')->firstOrFail();
        $persona = Persona::query()->where('identificacion', '0977777777')->firstOrFail();
        $empleado = Empleado::query()->where('persona_id', $persona->id)->firstOrFail();

        expect($import->tablas)->toBe('users,personas,empleados')
            ->and($import->mensaje)->toBe('Importados: 1. Fallidos: 0.')
            ->and($user->hasRole(Role::Docente))->toBeTrue()
            ->and($user->establecimiento_id)->toBe($establecimiento->id)
            ->and(Hash::check('0977777777', $user->password))->toBeTrue()
            ->and($persona->nombres)->toBe('Carla')
            ->and($empleado->tipo_contrato_id)->toBe($tipoContrato->id)
            ->and($empleado->horas)->toBe(0);
    });

    it('imports docentes from an excel file', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        Sys_TipoContrato::factory()->create();

        $this->actingAs($admin)
            ->post(route('Admin.padres.import.store'), importPayload(representantesXlsx([
                representantesHeader(),
                ['C', '0988888888', 'diego.paz@example.com', 'Diego', 'Paz'],
            ]), 'docentes'));

        $import = ImportData::query()->firstOrFail();
        $persona = Persona::query()->where('identificacion', '0988888888')->firstOrFail();

        expect($import->tipo_archivo)->toBe('xlsx')
            ->and($import->mensaje)->toBe('Importados: 1. Fallidos: 0.');
        $this->assertDatabaseHas('users', ['email' => 'diego.paz@example.com']);
        expect(Empleado::query()->where('persona_id', $persona->id)->exists())->toBeTrue();
    });

    it('does not import docentes when there is no contract type in catalogs', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        Sys_Provincia::factory()->create(['pais_id' => $pais->id]);

        $this->actingAs($admin)
            ->post(route('Admin.padres.import.store'), importPayload(representantesCsv([
                representantesHeader(),
                ['C', '0977777777', 'carla.rios@example.com', 'Carla', 'Ríos'],
            ]), 'docentes'));

        $this->assertDatabaseMissing('users', ['email' => 'carla.rios@example.com']);
        $this->assertDatabaseHas('import_data_detalles', [
            'descripcion' => 'No hay un tipo de contrato en catálogos para completar los docentes.',
        ]);
    });
});

describe('show', function () {
    it('shows imported and failed rows for the establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $import = ImportData::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'mensaje' => 'Importados: 1. Fallidos: 1.',
        ]);
        $import->detalles()->create([
            'num_fila' => 2,
            'identificacion' => '0911111111',
            'descripcion' => 'Importado correctamente',
        ]);
        $import->detalles()->create([
            'num_fila' => 3,
            'identificacion' => "<script>alert('xss')</script>",
            'descripcion' => 'El correo no es válido.',
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.padres.import.show', $import))
            ->assertOk()
            ->assertSee('Importados: 1. Fallidos: 1.')
            ->assertSee('Importado')
            ->assertSee('Fallido')
            ->assertSee('El correo no es válido.')
            ->assertSee("<script>alert('xss')</script>")
            ->assertDontSee("<script>alert('xss')</script>", false);
    });

    it('links back to alumnos after an alumnos import', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $import = ImportData::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'tablas' => 'users,personas,alumnos',
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.padres.import.show', $import))
            ->assertOk()
            ->assertSee('Volver a alumnos')
            ->assertDontSee('Volver a padres');
    });

    it('links back to empleados after a docentes import', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $import = ImportData::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'tablas' => 'users,personas,empleados',
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.padres.import.show', $import))
            ->assertOk()
            ->assertSee('Volver a empleados')
            ->assertDontSee('Volver a padres');
    });

    it('returns 404 when viewing an import from another establishment', function () {
        $admin = adminOf(Establecimiento::factory()->create());
        $import = ImportData::factory()->create();

        $this->actingAs($admin)
            ->get(route('Admin.padres.import.show', $import))
            ->assertNotFound();
    });
});
