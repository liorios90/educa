<?php

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\ImportData;
use App\Models\Padre;
use App\Models\Persona;
use App\Models\Sys_Pais;
use App\Models\Sys_Provincia;
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

describe('create', function () {
    it('allows administrators to open the import form', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('Admin.padres.import'))
            ->assertOk()
            ->assertSee('Importar representantes')
            ->assertSee('tipo_identificacion');
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
            ->post(route('Admin.padres.import.store'), [
                'archivo' => representantesCsv([
                    representantesHeader(),
                    ['C', '0911111111', 'ana.mora@example.com', 'Ana', 'Mora'],
                    ['C', '0922222222', 'luis.vera@example.com', 'Luis', 'Vera'],
                ]),
            ]);

        $import = ImportData::query()->firstOrFail();

        $response->assertRedirect(route('Admin.padres.import.show', $import))
            ->assertSessionHas('status', 'representantes-imported');

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
            ->post(route('Admin.padres.import.store'), [
                'archivo' => representantesXlsx([
                    representantesHeader(),
                    ['C', '0944444444', 'sofia.nunez@example.com', 'Sofía', 'Núñez'],
                ]),
            ]);

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
            ->post(route('Admin.padres.import.store'), [
                'archivo' => representantesCsv([
                    representantesHeader(),
                    ['C', '0911111111', 'ana.mora@example.com', 'Ana', 'Mora'],
                    ['X', '0922222222', 'malo@example.com', 'Luis', 'Vera'],
                    ['C', '0933333333', 'ya.existe@example.com', 'Eva', 'Sol'],
                    ['C', '', 'sin.id@example.com', 'Pia', 'Rios'],
                ]),
            ]);

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
            ->post(route('Admin.padres.import.store'), [
                'archivo' => representantesCsv([
                    representantesHeader(),
                    ['C', '0911111111', 'ana.mora@example.com', 'Ana', 'Mora'],
                ]),
            ]);

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
            ->post(route('Admin.padres.import.store'), [
                'archivo' => representantesCsv([
                    ['identificacion', 'nombres'],
                    ['0911111111', 'Ana'],
                ]),
            ]);

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
            ->post(route('Admin.padres.import.store'), [])
            ->assertRedirect(route('Admin.padres.import'))
            ->assertSessionHasErrors('archivo');

        expect(ImportData::query()->exists())->toBeFalse();
    });

    it('forbids systems users from importing representantes', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->post(route('Admin.padres.import.store'), [
                'archivo' => representantesCsv([representantesHeader()]),
            ])
            ->assertForbidden();
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

    it('returns 404 when viewing an import from another establishment', function () {
        $admin = adminOf(Establecimiento::factory()->create());
        $import = ImportData::factory()->create();

        $this->actingAs($admin)
            ->get(route('Admin.padres.import.show', $import))
            ->assertNotFound();
    });
});
