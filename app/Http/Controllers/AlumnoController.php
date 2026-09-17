<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UpsertAlumnoRequest;
use App\Models\Alumno;
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

class AlumnoController extends Controller
{
    public function index(Request $request): View
    {
        $establecimientoId = $this->establecimientoId($request);

        return view('admin.alumnos.index', [
            'alumnos' => Alumno::query()
                ->select('alumnos.*')
                ->with(['persona.user', 'padre.persona'])
                ->join('personas', 'personas.id', '=', 'alumnos.persona_id')
                ->where('personas.establecimiento_id', $establecimientoId)
                ->orderBy('personas.apellidos')
                ->orderBy('personas.nombres')
                ->orderBy('alumnos.id')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $establecimientoId = $this->establecimientoId($request);

        return view('admin.alumnos.create', $this->catalogOptions($establecimientoId));
    }

    public function store(UpsertAlumnoRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $this->persist($request);
        });

        return redirect()
            ->route('Admin.alumnos')
            ->with('status', 'alumno-created');
    }

    public function edit(Request $request, Alumno $alumno): View
    {
        $alumno = $this->alumnoForAdmin($request, $alumno);

        return view('admin.alumnos.edit', [
            ...$this->catalogOptions($this->establecimientoId($request)),
            'alumno' => $alumno,
            'persona' => $alumno->persona,
            'cuenta' => $alumno->persona?->user,
        ]);
    }

    public function update(UpsertAlumnoRequest $request, Alumno $alumno): RedirectResponse
    {
        $alumno = $this->alumnoForAdmin($request, $alumno);

        DB::transaction(function () use ($request, $alumno): void {
            $this->persist($request, $alumno);
        });

        return redirect()
            ->route('Admin.alumnos')
            ->with('status', 'alumno-updated');
    }

    public function destroy(Request $request, Alumno $alumno): RedirectResponse
    {
        $alumno = $this->alumnoForAdmin($request, $alumno);

        DB::transaction(function () use ($alumno): void {
            $persona = $alumno->persona;
            $user = $persona?->user;

            $alumno->delete();
            $persona?->delete();
            $user?->delete();
        });

        return redirect()
            ->route('Admin.alumnos')
            ->with('status', 'alumno-deleted');
    }

    /**
     * @return array{
     *     paises: list<array{id: int, nombre: string}>,
     *     provincias: list<array{id: int, nombre: string, pais_id: int}>,
     *     padres: list<array{id: int, nombre: string}>,
     *     tiposIdentificacion: array<int, string>,
     *     generos: array<int, string>
     * }
     */
    private function catalogOptions(int $establecimientoId): array
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
            'padres' => Padre::query()
                ->select('padres.*')
                ->with('persona')
                ->join('personas', 'personas.id', '=', 'padres.persona_id')
                ->where('personas.establecimiento_id', $establecimientoId)
                ->orderBy('personas.apellidos')
                ->orderBy('personas.nombres')
                ->orderBy('padres.id')
                ->get()
                ->map(fn (Padre $padre): array => [
                    'id' => $padre->id,
                    'nombre' => trim(($padre->persona?->apellidos ?? '').' '.($padre->persona?->nombres ?? '')),
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
        ];
    }

    private function persist(UpsertAlumnoRequest $request, ?Alumno $alumno = null): void
    {
        $establecimientoId = $this->establecimientoId($request);
        $actorName = (string) $request->user()?->name;
        $persona = $alumno?->persona;
        $user = $persona?->user;

        RoleModel::findOrCreate(Role::Alumno->value, 'web');

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
            $user->assignRole(Role::Alumno);
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
            $user->syncRoles([Role::Alumno]);
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

        $alumnoAttributes = [
            'persona_id' => $persona->id,
            'padre_id' => $request->input('padre_id'),
            'contacto_emergencia' => $request->string('contacto_emergencia')->toString(),
            'usuario' => $actorName,
            'activo' => $request->boolean('activo') ? 1 : 0,
        ];

        if ($alumno === null) {
            Alumno::query()->create($alumnoAttributes);

            return;
        }

        $alumno->update($alumnoAttributes);
    }

    private function alumnoForAdmin(Request $request, Alumno $alumno): Alumno
    {
        $alumno->loadMissing(['persona.user', 'padre.persona']);

        abort_unless(
            (int) $alumno->persona?->establecimiento_id === $this->establecimientoId($request),
            404,
        );

        return $alumno;
    }

    private function establecimientoId(Request $request): int
    {
        $establecimientoId = $request->user()?->establecimiento_id;

        abort_if($establecimientoId === null, 403);

        return (int) $establecimientoId;
    }
}
