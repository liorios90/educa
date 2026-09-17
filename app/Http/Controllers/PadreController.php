<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UpsertPadreRequest;
use App\Models\Padre;
use App\Models\Persona;
use App\Models\Sys_Pais;
use App\Models\Sys_Provincia;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Role as RoleModel;

class PadreController extends Controller
{
    public function index(Request $request): View
    {
        $establecimientoId = $this->establecimientoId($request);

        return view('admin.padres.index', [
            'padres' => Padre::query()
                ->select('padres.*')
                ->with(['persona.user'])
                ->join('personas', 'personas.id', '=', 'padres.persona_id')
                ->where('personas.establecimiento_id', $establecimientoId)
                ->orderBy('personas.apellidos')
                ->orderBy('personas.nombres')
                ->orderBy('padres.id')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->establecimientoId($request);

        return view('admin.padres.create', $this->catalogOptions());
    }

    public function store(UpsertPadreRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $this->persist($request);
        });

        return redirect()
            ->route('Admin.padres')
            ->with('status', 'padre-created');
    }

    public function edit(Request $request, Padre $padre): View
    {
        $padre = $this->padreForAdmin($request, $padre);

        return view('admin.padres.edit', [
            ...$this->catalogOptions(),
            'padre' => $padre,
            'persona' => $padre->persona,
            'cuenta' => $padre->persona?->user,
        ]);
    }

    public function update(UpsertPadreRequest $request, Padre $padre): RedirectResponse
    {
        $padre = $this->padreForAdmin($request, $padre);

        DB::transaction(function () use ($request, $padre): void {
            $this->persist($request, $padre);
        });

        return redirect()
            ->route('Admin.padres')
            ->with('status', 'padre-updated');
    }

    public function destroy(Request $request, Padre $padre): RedirectResponse
    {
        $padre = $this->padreForAdmin($request, $padre);

        DB::transaction(function () use ($padre): void {
            $persona = $padre->persona;
            $user = $persona?->user;

            $padre->delete();
            $persona?->delete();
            $user?->delete();
        });

        return redirect()
            ->route('Admin.padres')
            ->with('status', 'padre-deleted');
    }

    /**
     * @return array{
     *     paises: list<array{id: int, nombre: string}>,
     *     provincias: list<array{id: int, nombre: string, pais_id: int}>,
     *     tiposIdentificacion: array<int, string>,
     *     generos: array<int, string>,
     *     estadosCiviles: list<string>
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
            'estadosCiviles' => [
                'Soltero',
                'Casado',
                'Divorciado',
                'Unión libre',
                'Viudo',
            ],
        ];
    }

    private function persist(UpsertPadreRequest $request, ?Padre $padre = null): void
    {
        $establecimientoId = $this->establecimientoId($request);
        $actorName = (string) $request->user()?->name;
        $persona = $padre?->persona;
        $user = $persona?->user;

        RoleModel::findOrCreate(Role::Padre->value, 'web');

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
            $user->assignRole(Role::Padre);
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
            $user->syncRoles([Role::Padre]);
        }

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

        $padreAttributes = [
            'persona_id' => $persona->id,
            'estado_civil_id' => $request->string('estado_civil_id')->toString(),
            'vive_con_estudiante' => $request->boolean('vive_con_estudiante') ? 1 : 0,
            'titulo' => $request->string('titulo')->toString(),
            'usuario' => $actorName,
            'activo' => $request->boolean('activo') ? 1 : 0,
        ];

        if ($padre === null) {
            Padre::query()->create($padreAttributes);

            return;
        }

        $padre->update($padreAttributes);
    }

    private function padreForAdmin(Request $request, Padre $padre): Padre
    {
        $padre->loadMissing(['persona.user']);

        abort_unless(
            (int) $padre->persona?->establecimiento_id === $this->establecimientoId($request),
            404,
        );

        return $padre;
    }

    private function establecimientoId(Request $request): int
    {
        $establecimientoId = $request->user()?->establecimiento_id;

        abort_if($establecimientoId === null, 403);

        return (int) $establecimientoId;
    }
}
