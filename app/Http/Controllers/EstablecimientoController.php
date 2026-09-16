<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UpsertEstablecimientoRequest;
use App\Models\Establecimiento;
use App\Models\Sys_Circuito;
use App\Models\Sys_Distrito;
use App\Models\Sys_Zona;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Spatie\Permission\Models\Role as RoleModel;

class EstablecimientoController extends Controller
{
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
        return view('sistemas.establecimientos.create', $this->catalogOptions());
    }

    public function store(UpsertEstablecimientoRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $establecimiento = Establecimiento::query()->create($this->attributesFrom($request));
            $this->syncAdministrador($establecimiento, $request);
        });

        return redirect()
            ->route('sistemas.establecimientos')
            ->with('status', 'establecimiento-created');
    }

    public function edit(Establecimiento $establecimiento): View
    {
        return view('sistemas.establecimientos.edit', [
            ...$this->catalogOptions(),
            'establecimiento' => $establecimiento,
            'administrador' => $establecimiento->administrador(),
        ]);
    }

    public function update(UpsertEstablecimientoRequest $request, Establecimiento $establecimiento): RedirectResponse
    {
        DB::transaction(function () use ($request, $establecimiento): void {
            $establecimiento->update($this->attributesFrom($request, $establecimiento));
            $this->syncAdministrador($establecimiento, $request);
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
     *     circuitos: list<array{id: int, nombre: string, distrito_id: int|null}>
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
        ];
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
