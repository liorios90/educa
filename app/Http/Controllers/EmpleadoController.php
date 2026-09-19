<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UpsertEmpleadoRequest;
use App\Models\Empleado;
use App\Models\Persona;
use App\Models\Sys_Funcion;
use App\Models\Sys_Pais;
use App\Models\Sys_Provincia;
use App\Models\Sys_TipoContrato;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Role as RoleModel;

class EmpleadoController extends Controller
{
    public function index(Request $request): View
    {
        $establecimientoId = $this->establecimientoId($request);

        return view('admin.empleados.index', [
            'empleados' => Empleado::query()
                ->select('empleados.*')
                ->with(['persona.user.roles', 'tipoContrato', 'funcion'])
                ->join('personas', 'personas.id', '=', 'empleados.persona_id')
                ->where('personas.establecimiento_id', $establecimientoId)
                ->orderBy('personas.apellidos')
                ->orderBy('personas.nombres')
                ->orderBy('empleados.id')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->establecimientoId($request);

        return view('admin.empleados.create', $this->catalogOptions());
    }

    public function store(UpsertEmpleadoRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $this->persist($request);
        });

        return redirect()
            ->route('Admin.empleados')
            ->with('status', 'empleado-created');
    }

    public function edit(Request $request, Empleado $empleado): View
    {
        $empleado = $this->empleadoForAdmin($request, $empleado);

        return view('admin.empleados.edit', [
            ...$this->catalogOptions(),
            'empleado' => $empleado,
            'persona' => $empleado->persona,
            'cuenta' => $empleado->persona?->user,
        ]);
    }

    public function update(UpsertEmpleadoRequest $request, Empleado $empleado): RedirectResponse
    {
        $empleado = $this->empleadoForAdmin($request, $empleado);

        DB::transaction(function () use ($request, $empleado): void {
            $this->persist($request, $empleado);
        });

        return redirect()
            ->route('Admin.empleados')
            ->with('status', 'empleado-updated');
    }

    public function destroy(Request $request, Empleado $empleado): RedirectResponse
    {
        $empleado = $this->empleadoForAdmin($request, $empleado);

        DB::transaction(function () use ($empleado): void {
            $persona = $empleado->persona;
            $user = $persona?->user;

            $empleado->delete();
            $persona?->delete();
            $user?->delete();
        });

        return redirect()
            ->route('Admin.empleados')
            ->with('status', 'empleado-deleted');
    }

    /**
     * @return array{
     *     paises: list<array{id: int, nombre: string}>,
     *     provincias: list<array{id: int, nombre: string, pais_id: int}>,
     *     tiposContrato: list<array{id: int, nombre: string}>,
     *     funciones: list<array{id: int, nombre: string}>,
     *     roles: list<Role>,
     *     tiposIdentificacion: array<int, string>,
     *     generos: array<int, string>
     * }
     */
    private function catalogOptions(): array
    {
        return [
            'paises' => Sys_Pais::query()
                ->orderBy('nombre')
                ->orderBy('id')
                ->get(['id', 'nombre'])
                ->map(fn (Sys_Pais $pais): array => [
                    'id' => $pais->id,
                    'nombre' => $pais->nombre,
                ])
                ->values()
                ->all(),
            'provincias' => Sys_Provincia::query()
                ->orderBy('nombre')
                ->orderBy('id')
                ->get(['id', 'nombre', 'pais_id'])
                ->map(fn (Sys_Provincia $provincia): array => [
                    'id' => $provincia->id,
                    'nombre' => $provincia->nombre,
                    'pais_id' => $provincia->pais_id,
                ])
                ->values()
                ->all(),
            'tiposContrato' => Sys_TipoContrato::query()
                ->orderBy('nombre')
                ->orderBy('id')
                ->get(['id', 'nombre'])
                ->map(fn (Sys_TipoContrato $tipoContrato): array => [
                    'id' => $tipoContrato->id,
                    'nombre' => $tipoContrato->nombre,
                ])
                ->values()
                ->all(),
            'funciones' => Sys_Funcion::query()
                ->orderBy('nombre')
                ->orderBy('id')
                ->get(['id', 'nombre'])
                ->map(fn (Sys_Funcion $funcion): array => [
                    'id' => $funcion->id,
                    'nombre' => $funcion->nombre,
                ])
                ->values()
                ->all(),
            'roles' => Role::assignableToEmpleado(),
            'tiposIdentificacion' => [
                1 => 'Cédula',
                2 => 'Pasaporte',
                3 => 'RUC',
            ],
            'generos' => [
                1 => 'Masculino',
                2 => 'Femenino',
                3 => 'Otro',
            ],
        ];
    }

    private function persist(UpsertEmpleadoRequest $request, ?Empleado $empleado = null): void
    {
        $establecimientoId = $this->establecimientoId($request);
        $actorName = (string) $request->user()?->name;
        $persona = $empleado?->persona;
        $user = $persona?->user;

        $userAttributes = [
            'name' => trim($request->string('nombres')->toString().' '.$request->string('apellidos')->toString()),
            'email' => $request->string('email')->toString(),
            'establecimiento_id' => $establecimientoId,
        ];

        if ($request->filled('password')) {
            $userAttributes['password'] = $request->string('password')->toString();
        }

        if ($user === null) {
            $user = User::query()->create($userAttributes);
        } else {
            $user->fill([
                'name' => $userAttributes['name'],
                'email' => $userAttributes['email'],
            ]);

            if (isset($userAttributes['password'])) {
                $user->password = $userAttributes['password'];
            }

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();
        }

        $this->syncRoles($user, $request->selectedRoles());

        $personaAttributes = [
            ...$request->safe()->only([
                'tipo_identificacion_id',
                'identificacion',
                'nombres',
                'apellidos',
                'genero_id',
                'fecha_nacimiento',
                'ciudad_nacimiento',
                'provincia_id',
                'parroquia',
                'direccion',
                'telefono1',
                'telefono2',
                'nacionalidad_id',
                'lote',
            ]),
            'user_id' => $user->id,
            'establecimiento_id' => $establecimientoId,
            'usuario' => $actorName,
            'activo' => $request->boolean('activo') ? 1 : 0,
        ];

        if ($persona === null) {
            $persona = Persona::query()->create($personaAttributes);
        } else {
            $persona->update($personaAttributes);
        }

        $empleadoAttributes = [
            'persona_id' => $persona->id,
            'tipo_contrato_id' => $request->integer('tipo_contrato_id'),
            'cargo_id' => $request->input('cargo_id'),
            'funcion_id' => $request->input('funcion_id'),
            'horas' => $request->integer('horas'),
            'anios_experiencia' => $request->integer('anios_experiencia'),
            'anios_instituto' => $request->integer('anios_instituto'),
            'contacto_emergencia' => $request->string('contacto_emergencia')->toString(),
            'contacto_num' => $request->string('contacto_num')->toString(),
            'usuario' => $actorName,
            'activo' => $request->boolean('activo') ? 1 : 0,
        ];

        if ($empleado === null) {
            Empleado::query()->create($empleadoAttributes);

            return;
        }

        $empleado->update($empleadoAttributes);
    }

    private function empleadoForAdmin(Request $request, Empleado $empleado): Empleado
    {
        $empleado->loadMissing(['persona.user.roles', 'tipoContrato', 'funcion']);

        abort_unless(
            (int) $empleado->persona?->establecimiento_id === $this->establecimientoId($request),
            404,
        );

        return $empleado;
    }

    private function establecimientoId(Request $request): int
    {
        $establecimientoId = $request->user()?->establecimiento_id;

        abort_if($establecimientoId === null, 403);

        return (int) $establecimientoId;
    }

    /**
     * @param  list<Role>  $roles
     */
    private function syncRoles(User $user, array $roles): void
    {
        foreach ($roles as $role) {
            RoleModel::findOrCreate($role->value, 'web');
        }

        $user->syncRoles(array_map(fn (Role $role): string => $role->value, $roles));
    }
}
