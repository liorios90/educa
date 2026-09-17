@php
    $user = $user ?? null;
    $selectedRoles = collect(old('roles', $user?->roles->pluck('name')->all() ?? []))
        ->map(fn ($role): string => (string) $role)
        ->values()
        ->all();
    $selectedEstablecimiento = old('establecimiento_id', $user?->establecimiento_id);
@endphp

<div x-data="{
    roles: {{ \Illuminate\Support\Js::from($selectedRoles) }},
    rolesWithEstablecimiento: {{ \Illuminate\Support\Js::from($rolesWithEstablecimiento) }},
    get needsEstablecimiento() {
        return this.roles.some((role) => this.rolesWithEstablecimiento.includes(role));
    }
}">
    <fieldset>
        <legend class="block text-sm font-medium text-gray-700">Roles</legend>
        <p class="mt-1 text-xs text-slate-500">Puedes asignar más de un rol. Al iniciar sesión, la persona elige con cuál entrar.</p>
        <div class="mt-3 flex flex-col gap-2">
            @foreach ($roles as $role)
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        name="roles[]"
                        value="{{ $role->value }}"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        x-model="roles"
                        @checked(in_array($role->value, $selectedRoles, true))
                    >
                    {{ $role->label() }}
                </label>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('roles')" />
    </fieldset>

    <div class="mt-6" x-show="needsEstablecimiento" x-cloak>
        <x-input-label for="establecimiento_id" value="Establecimiento" />
        <select
            id="establecimiento_id"
            name="establecimiento_id"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            :required="needsEstablecimiento"
        >
            <option value="">Selecciona un establecimiento</option>
            @foreach ($establecimientos as $establecimiento)
                <option value="{{ $establecimiento->id }}" @selected((string) $selectedEstablecimiento === (string) $establecimiento->id)>
                    {{ $establecimiento->nombre }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Administrador, Secretaría y Padre deben pertenecer a un establecimiento.</p>
        <x-input-error class="mt-2" :messages="$errors->get('establecimiento_id')" />
    </div>
</div>
