<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\ImportData;
use App\Models\Padre;
use App\Models\Persona;
use App\Models\Sys_Pais;
use App\Models\Sys_Provincia;
use App\Models\Sys_TipoContrato;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role as RoleModel;

class ImportRepresentantesService
{
    /**
     * @var list<string>
     */
    private const BASE_COLUMNS = [
        'tipo_identificacion',
        'identificacion',
        'email',
        'nombres',
        'apellidos',
    ];

    public function __construct(private SpreadsheetReader $reader) {}

    public function import(User $actor, UploadedFile $file, string $tipo): ImportData
    {
        $establecimientoId = (int) $actor->establecimiento_id;
        $extension = strtolower($file->getClientOriginalExtension());
        $import = ImportData::query()->create([
            'nombre' => Str::limit($file->getClientOriginalName(), 255, ''),
            'tablas' => $this->tablas($tipo),
            'tipo_archivo' => $extension,
            'mensaje' => 'Procesando importación.',
            'establecimiento_id' => $establecimientoId,
            'usuario' => (string) $actor->name,
            'activo' => 1,
        ]);

        $catalog = $this->catalogDefaults($tipo);

        if ($catalog === null) {
            $import->detalles()->create([
                'num_fila' => null,
                'identificacion' => null,
                'descripcion' => $this->missingCatalogMessage($tipo),
            ]);
            $import->update([
                'mensaje' => 'Importados: 0. Fallidos: 1.',
            ]);

            return $import->load('detalles');
        }

        try {
            $rows = $this->reader->rows($file->getRealPath() ?: $file->getPathname(), $extension);
        } catch (\Throwable $exception) {
            $import->detalles()->create([
                'num_fila' => null,
                'identificacion' => null,
                'descripcion' => $exception->getMessage(),
            ]);
            $import->update([
                'mensaje' => 'Importados: 0. Fallidos: 1.',
            ]);

            return $import->load('detalles');
        }

        if ($rows === []) {
            $import->detalles()->create([
                'num_fila' => null,
                'identificacion' => null,
                'descripcion' => 'El archivo no contiene filas de datos.',
            ]);
            $import->update([
                'mensaje' => 'Importados: 0. Fallidos: 1.',
            ]);

            return $import->load('detalles');
        }

        $requiredColumns = $this->requiredColumns($tipo);
        $missingColumns = array_values(array_diff($requiredColumns, array_keys($rows[0]['values'])));

        if ($missingColumns !== []) {
            $import->detalles()->create([
                'num_fila' => 1,
                'identificacion' => null,
                'descripcion' => 'Faltan columnas: '.implode(', ', $missingColumns).'.',
            ]);
            $import->update([
                'mensaje' => 'Importados: 0. Fallidos: 1.',
            ]);

            return $import->load('detalles');
        }

        RoleModel::findOrCreate($this->roleFor($tipo)->value, 'web');

        $imported = 0;
        $failed = 0;
        $seenIdentificaciones = [];
        $seenEmails = [];
        $padres = [];

        foreach ($rows as $row) {
            $values = $row['values'];
            $identificacion = $values['identificacion'] ?? '';
            $error = $this->validateRow($values, $seenIdentificaciones, $seenEmails, $tipo, $establecimientoId, $padres);

            if ($error !== null) {
                $import->detalles()->create([
                    'num_fila' => $row['num_fila'],
                    'identificacion' => $identificacion !== '' ? $identificacion : null,
                    'descripcion' => $error,
                ]);
                $failed++;

                continue;
            }

            $email = Str::lower($values['email']);
            $seenIdentificaciones[$identificacion] = true;
            $seenEmails[$email] = true;

            try {
                DB::transaction(function () use ($actor, $establecimientoId, $catalog, $values, $email, $identificacion, $tipo, $padres): void {
                    $this->persistRow($actor, $establecimientoId, $catalog, $values, $email, $identificacion, $tipo, $padres);
                });
            } catch (\Throwable $exception) {
                unset($seenIdentificaciones[$identificacion], $seenEmails[$email]);
                $import->detalles()->create([
                    'num_fila' => $row['num_fila'],
                    'identificacion' => $identificacion,
                    'descripcion' => 'No se pudo grabar el registro.',
                ]);
                $failed++;

                continue;
            }

            $import->detalles()->create([
                'num_fila' => $row['num_fila'],
                'identificacion' => $identificacion,
                'descripcion' => 'Importado correctamente',
            ]);
            $imported++;
        }

        $import->update([
            'mensaje' => "Importados: {$imported}. Fallidos: {$failed}.",
        ]);

        return $import->load('detalles');
    }

    /**
     * @return list<string>
     */
    private function requiredColumns(string $tipo): array
    {
        if ($tipo !== 'alumnos') {
            return self::BASE_COLUMNS;
        }

        return [...self::BASE_COLUMNS, 'identificacion_representante'];
    }

    /**
     * @param  array<string, string>  $values
     * @param  array<string, true>  $seenIdentificaciones
     * @param  array<string, true>  $seenEmails
     * @param  array<string, Padre|null>  $padres
     */
    private function validateRow(
        array $values,
        array $seenIdentificaciones,
        array $seenEmails,
        string $tipo,
        int $establecimientoId,
        array &$padres,
    ): ?string {
        foreach ($this->requiredColumns($tipo) as $column) {
            if (($values[$column] ?? '') === '') {
                return 'Falta '.$column.'.';
            }
        }

        if ($this->tipoIdentificacionId($values['tipo_identificacion']) === null) {
            return 'Tipo de identificación no válido. Usa C (cédula), P (pasaporte) o R (RUC).';
        }

        $email = Str::lower($values['email']);
        $emailValidator = Validator::make(
            ['email' => $email],
            ['email' => ['email']],
        );

        if ($emailValidator->fails()) {
            return 'El correo no es válido.';
        }

        if (isset($seenIdentificaciones[$values['identificacion']])) {
            return 'La identificación está duplicada en el archivo.';
        }

        if (isset($seenEmails[$email])) {
            return 'El correo está duplicado en el archivo.';
        }

        if (Persona::query()->where('identificacion', $values['identificacion'])->exists()) {
            return 'La identificación ya está registrada.';
        }

        if (User::query()->where('email', $email)->exists()) {
            return 'El correo ya está registrado.';
        }

        if ($tipo === 'alumnos') {
            $identificacionRepresentante = $values['identificacion_representante'];

            if ($identificacionRepresentante === $values['identificacion']) {
                return 'La identificación del representante no puede ser la del alumno.';
            }

            if ($this->padreOfEstablecimiento($identificacionRepresentante, $establecimientoId, $padres) === null) {
                return 'No se encontró el representante con esa identificación en este instituto.';
            }
        }

        return null;
    }

    /**
     * @param  array{pais_id: int, provincia_id: int, tipo_contrato_id?: int}  $catalog
     * @param  array<string, string>  $values
     * @param  array<string, Padre|null>  $padres
     */
    private function persistRow(
        User $actor,
        int $establecimientoId,
        array $catalog,
        array $values,
        string $email,
        string $identificacion,
        string $tipo,
        array &$padres,
    ): void {
        $persona = $this->persistPersona(
            $actor,
            $establecimientoId,
            $catalog,
            $values,
            $email,
            $identificacion,
            $this->roleFor($tipo),
        );

        match ($tipo) {
            'alumnos' => $this->persistAlumno($actor, $establecimientoId, $values, $persona, $padres),
            'docentes' => $this->persistDocente($actor, $catalog, $persona),
            default => $this->persistPadre($actor, $persona),
        };
    }

    private function persistPadre(User $actor, Persona $persona): void
    {
        Padre::query()->create([
            'persona_id' => $persona->id,
            'estado_civil_id' => 'No especificado',
            'vive_con_estudiante' => 0,
            'titulo' => 'No especificado',
            'usuario' => (string) $actor->name,
            'activo' => 1,
        ]);
    }

    /**
     * @param  array<string, string>  $values
     * @param  array<string, Padre|null>  $padres
     */
    private function persistAlumno(
        User $actor,
        int $establecimientoId,
        array $values,
        Persona $persona,
        array &$padres,
    ): void {
        $padre = $this->padreOfEstablecimiento($values['identificacion_representante'], $establecimientoId, $padres);

        if ($padre === null) {
            throw new RuntimeException('No se encontró el representante con esa identificación en este instituto.');
        }

        Alumno::query()->create([
            'persona_id' => $persona->id,
            'padre_id' => $padre->id,
            'contacto_emergencia' => '-',
            'usuario' => (string) $actor->name,
            'activo' => 1,
        ]);
    }

    /**
     * @param  array{pais_id: int, provincia_id: int, tipo_contrato_id?: int}  $catalog
     */
    private function persistDocente(User $actor, array $catalog, Persona $persona): void
    {
        $tipoContratoId = $catalog['tipo_contrato_id'] ?? null;

        if ($tipoContratoId === null) {
            throw new RuntimeException('No hay un tipo de contrato en catálogos para completar los docentes.');
        }

        Empleado::query()->create([
            'persona_id' => $persona->id,
            'tipo_contrato_id' => $tipoContratoId,
            'cargo_id' => null,
            'funcion_id' => null,
            'horas' => 0,
            'anios_experiencia' => 0,
            'anios_instituto' => 0,
            'contacto_emergencia' => 'No especificado',
            'contacto_num' => '-',
            'usuario' => (string) $actor->name,
            'activo' => 1,
        ]);
    }

    /**
     * @param  array{pais_id: int, provincia_id: int, tipo_contrato_id?: int}  $catalog
     * @param  array<string, string>  $values
     */
    private function persistPersona(
        User $actor,
        int $establecimientoId,
        array $catalog,
        array $values,
        string $email,
        string $identificacion,
        Role $role,
    ): Persona {
        $user = User::query()->create([
            'name' => trim($values['nombres'].' '.$values['apellidos']),
            'email' => $email,
            'password' => $identificacion,
            'establecimiento_id' => $establecimientoId,
        ]);
        $user->assignRole($role);

        return Persona::query()->create([
            'user_id' => $user->id,
            'tipo_identificacion_id' => $this->tipoIdentificacionId($values['tipo_identificacion']),
            'identificacion' => $identificacion,
            'nombres' => $values['nombres'],
            'apellidos' => $values['apellidos'],
            'genero_id' => 3,
            'ciudad_nacimiento' => 'Pendiente',
            'provincia_id' => $catalog['provincia_id'],
            'parroquia' => 'Pendiente',
            'direccion' => 'Pendiente',
            'telefono1' => '-',
            'telefono2' => '-',
            'nacionalidad_id' => $catalog['pais_id'],
            'usuario' => (string) $actor->name,
            'activo' => 1,
            'establecimiento_id' => $establecimientoId,
        ]);
    }

    /**
     * @param  array<string, Padre|null>  $padres
     */
    private function padreOfEstablecimiento(string $identificacion, int $establecimientoId, array &$padres): ?Padre
    {
        if (array_key_exists($identificacion, $padres)) {
            return $padres[$identificacion];
        }

        $padre = Padre::query()
            ->whereHas('persona', function ($query) use ($identificacion, $establecimientoId): void {
                $query->where('identificacion', $identificacion)
                    ->where('establecimiento_id', $establecimientoId);
            })
            ->first();

        $padres[$identificacion] = $padre;

        return $padre;
    }

    /**
     * @return array{pais_id: int, provincia_id: int, tipo_contrato_id?: int}|null
     */
    private function catalogDefaults(string $tipo): ?array
    {
        $pais = Sys_Pais::query()->orderBy('id')->first();

        if ($pais === null) {
            return null;
        }

        $provincia = Sys_Provincia::query()
            ->where('pais_id', $pais->id)
            ->orderBy('id')
            ->first();

        if ($provincia === null) {
            return null;
        }

        $catalog = [
            'pais_id' => $pais->id,
            'provincia_id' => $provincia->id,
        ];

        if ($tipo !== 'docentes') {
            return $catalog;
        }

        $tipoContrato = Sys_TipoContrato::query()->orderBy('id')->first();

        if ($tipoContrato === null) {
            return null;
        }

        $catalog['tipo_contrato_id'] = $tipoContrato->id;

        return $catalog;
    }

    private function missingCatalogMessage(string $tipo): string
    {
        if ($tipo === 'docentes' && Sys_Pais::query()->exists() && Sys_Provincia::query()->exists()) {
            return 'No hay un tipo de contrato en catálogos para completar los docentes.';
        }

        return match ($tipo) {
            'alumnos' => 'No hay un país y una provincia en catálogos para completar los alumnos.',
            'docentes' => 'No hay un país y una provincia en catálogos para completar los docentes.',
            default => 'No hay un país y una provincia en catálogos para completar los representantes.',
        };
    }

    private function tablas(string $tipo): string
    {
        return match ($tipo) {
            'alumnos' => 'users,personas,alumnos',
            'docentes' => 'users,personas,empleados',
            default => 'users,personas,padres',
        };
    }

    private function roleFor(string $tipo): Role
    {
        return match ($tipo) {
            'alumnos' => Role::Alumno,
            'docentes' => Role::Docente,
            default => Role::Padre,
        };
    }

    private function tipoIdentificacionId(string $value): ?int
    {
        $normalized = strtoupper(Str::ascii(trim($value)));

        return match ($normalized) {
            'C', 'CEDULA', '1' => 1,
            'P', 'PASAPORTE', '2' => 2,
            'R', 'RUC', '3' => 3,
            default => null,
        };
    }
}
