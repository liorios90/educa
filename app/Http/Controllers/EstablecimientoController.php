<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UpsertEstablecimientoRequest;
use App\Models\Establecimiento;
use App\Models\Sys_Circuito;
use App\Models\Sys_Distrito;
use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
use App\Models\Sys_Zona;
use App\Models\User;
use App\Services\SyncEstablecimientoModalidades;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Spatie\Permission\Models\Role as RoleModel;

class EstablecimientoController extends Controller
{
    public function __construct(private SyncEstablecimientoModalidades $syncModalidades) {}

    public function index(): View
    {
        return view('sistemas.establecimientos.index', [
            'establecimientos' => Establecimiento::query()
                ->with(['zona:id,nombre', 'distrito:id,nombre', 'circuito:id,nombre'])
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('sistemas.establecimientos.create', $this->formOptions());
    }

    public function store(UpsertEstablecimientoRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $establecimiento = Establecimiento::query()->create($this->attributesFrom($request));
            $this->syncAdministrador($establecimiento, $request);
            $this->syncModalidades->handle($establecimiento, $request->jornadasPorModalidad());
        });

        return redirect()
            ->route('sistemas.establecimientos')
            ->with('status', 'establecimiento-created');
    }

    public function edit(Establecimiento $establecimiento): View
    {
        return view('sistemas.establecimientos.edit', [
            ...$this->formOptions($establecimiento),
            'establecimiento' => $establecimiento,
            'administrador' => $establecimiento->administrador(),
        ]);
    }

    public function update(UpsertEstablecimientoRequest $request, Establecimiento $establecimiento): RedirectResponse
    {
        DB::transaction(function () use ($request, $establecimiento): void {
            $establecimiento->update($this->attributesFrom($request, $establecimiento));
            $this->syncAdministrador($establecimiento, $request);
            $this->syncModalidades->handle($establecimiento, $request->jornadasPorModalidad());
        });

        return redirect()
            ->route('sistemas.establecimientos')
            ->with('status', 'establecimiento-updated');
    }

    public function destroy(Establecimiento $establecimiento): RedirectResponse
    {
        DB::transaction(function () use ($establecimiento): void {
            $establecimiento->users()->get()->each(fn (User $user) => $user->delete());
            $establecimiento->delete();
        });

        return redirect()
            ->route('sistemas.establecimientos')
            ->with('status', 'establecimiento-deleted');
    }

    /**
     * @return array{
     *     zonas: list<array{id: int, nombre: string}>,
     *     distritos: list<array{id: int, nombre: string, zona_id: int}>,
     *     circuitos: list<array{id: int, nombre: string, distrito_id: int|null}>,
     *     modalidades: list<array{id: int, nombre: string}>,
     *     jornadas: list<array{id: int, nombre: string}>,
     *     selectedModalidadIds: list<string>,
     *     selectedJornadasPorModalidad: array<string, list<string>>
     * }
     */
    private function formOptions(?Establecimiento $establecimiento = null): array
    {
        return [
            ...$this->catalogOptions(),
            ...$this->selectedOfertas($establecimiento),
        ];
    }

    /**
     * @return array{
     *     zonas: list<array{id: int, nombre: string}>,
     *     distritos: list<array{id: int, nombre: string, zona_id: int}>,
     *     circuitos: list<array{id: int, nombre: string, distrito_id: int|null}>,
     *     modalidades: list<array{id: int, nombre: string}>,
     *     jornadas: list<array{id: int, nombre: string}>
     * }
     */
    private function catalogOptions(): array
    {
        return [
            'zonas' => Sys_Zona::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->map(fn (Sys_Zona $zona): array => [
                    'id' => $zona->id,
                    'nombre' => $zona->nombre,
                ])
                ->values()
                ->all(),
            'distritos' => Sys_Distrito::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'zona_id'])
                ->map(fn (Sys_Distrito $distrito): array => [
                    'id' => $distrito->id,
                    'nombre' => $distrito->nombre,
                    'zona_id' => $distrito->zona_id,
                ])
                ->values()
                ->all(),
            'circuitos' => Sys_Circuito::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'distrito_id'])
                ->map(fn (Sys_Circuito $circuito): array => [
                    'id' => $circuito->id,
                    'nombre' => $circuito->nombre,
                    'distrito_id' => $circuito->distrito_id,
                ])
                ->values()
                ->all(),
            'modalidades' => Sys_Modalidad::query()
                ->orderBy('id')
                ->get(['id', 'nombre'])
                ->map(fn (Sys_Modalidad $modalidad): array => [
                    'id' => $modalidad->id,
                    'nombre' => $modalidad->nombre,
                ])
                ->values()
                ->all(),
            'jornadas' => Sys_Jornada::query()
                ->orderBy('id')
                ->get(['id', 'nombre'])
                ->map(fn (Sys_Jornada $jornada): array => [
                    'id' => $jornada->id,
                    'nombre' => $jornada->nombre,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     selectedModalidadIds: list<string>,
     *     selectedJornadasPorModalidad: array<string, list<string>>
     * }
     */
    private function selectedOfertas(?Establecimiento $establecimiento): array
    {
        $modalidadIds = old('modalidades');
        $jornadas = old('jornadas');

        if (is_array($modalidadIds)) {
            return [
                'selectedModalidadIds' => array_map(strval(...), $modalidadIds),
                'selectedJornadasPorModalidad' => $this->stringIdsByModalidad(is_array($jornadas) ? $jornadas : []),
            ];
        }

        if ($establecimiento === null) {
            return [
                'selectedModalidadIds' => [],
                'selectedJornadasPorModalidad' => [],
            ];
        }

        $ofertas = $establecimiento->establecimientoModalidades()
            ->with('jornadas')
            ->orderBy('id')
            ->get();

        $selectedJornadas = [];

        foreach ($ofertas as $oferta) {
            $selectedJornadas[(string) $oferta->modalidad_id] = $oferta->jornadas
                ->pluck('id')
                ->map(fn (mixed $id): string => (string) $id)
                ->values()
                ->all();
        }

        return [
            'selectedModalidadIds' => $ofertas
                ->pluck('modalidad_id')
                ->map(fn (mixed $id): string => (string) $id)
                ->values()
                ->all(),
            'selectedJornadasPorModalidad' => $selectedJornadas,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $jornadas
     * @return array<string, list<string>>
     */
    private function stringIdsByModalidad(array $jornadas): array
    {
        $selected = [];

        foreach ($jornadas as $modalidadId => $jornadaIds) {
            if (! is_array($jornadaIds)) {
                continue;
            }

            $selected[(string) $modalidadId] = array_map(strval(...), $jornadaIds);
        }

        return $selected;
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesFrom(UpsertEstablecimientoRequest $request, ?Establecimiento $establecimiento = null): array
    {
        $attributes = $request->safe()->only($this->fillableAttributes());

        $logo = $request->file('logo');

        if ($logo instanceof UploadedFile) {
            if (is_string($establecimiento?->logo) && $establecimiento->logo !== '') {
                Storage::disk('public')->delete($establecimiento->logo);
            }

            $attributes['logo'] = $logo->store('establecimientos/logos', 'public');
        }

        return $attributes;
    }

    private function syncAdministrador(Establecimiento $establecimiento, UpsertEstablecimientoRequest $request): void
    {
        RoleModel::findOrCreate(Role::Admin->value, 'web');

        $administrador = $establecimiento->administrador();
        $attributes = [
            'name' => $request->validated('admin_name'),
            'email' => $request->validated('admin_email'),
            'establecimiento_id' => $establecimiento->id,
        ];

        if ($request->filled('admin_password')) {
            $attributes['password'] = $request->validated('admin_password');
        }

        if ($administrador === null) {
            User::query()->create($attributes)->assignRole(Role::Admin);

            return;
        }

        $administrador->fill([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
        ]);

        if (isset($attributes['password'])) {
            $administrador->password = $attributes['password'];
        }

        if ($administrador->isDirty('email')) {
            $administrador->email_verified_at = null;
        }

        $administrador->save();
        $administrador->syncRoles([Role::Admin]);
    }

    /**
     * @return list<string>
     */
    private function fillableAttributes(): array
    {
        return [
            'nombre',
            'descripcion',
            'direccion',
            'telefono',
            'representante',
            'codigo_amie',
            'regimen',
            'email',
            'usuario',
            'activo',
            'grupo_amie',
            'zona_id',
            'distrito_id',
            'circuito_id',
        ];
    }
}
