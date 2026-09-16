@php
    $user = $user ?? null;
    $selectedRole = old('role', $user?->roles->first()?->name);
    $selectedEstablecimiento = old('establecimiento_id', $user?->establecimiento_id);
@endphp

<div x-data="{
    role: {{ \Illuminate\Support\Js::from($selectedRole) }},
    rolesWithEstablecimiento: {{ \Illuminate\Support\Js::from($rolesWithEstablecimiento) }}
}">
    <div>
        <x-input-label for="role" value="Rol" />
        <select
            id="role"
            name="role"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            x-model="role"
            required
        >
            <option value="" disabled @selected($selectedRole === null)>Selecciona un rol</option>
            @foreach ($roles as $role)
                <option value="{{ $role->value }}" @selected($selectedRole === $role->value)>
                    {{ $role->label() }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('role')" />
    </div>

    <div class="mt-6" x-show="rolesWithEstablecimiento.includes(role)" x-cloak>
        <x-input-label for="establecimiento_id" value="Establecimiento" />
        <select
            id="establecimiento_id"
            name="establecimiento_id"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            :required="rolesWithEstablecimiento.includes(role)"
        >
            <option value="">Selecciona un establecimiento</option>
            @foreach ($establecimientos as $establecimiento)
                <option value="{{ $establecimiento->id }}" @selected((string) $selectedEstablecimiento === (string) $establecimiento->id)>
                    {{ $establecimiento->nombre }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Administrador y Secretaría deben pertenecer a un establecimiento.</p>
        <x-input-error class="mt-2" :messages="$errors->get('establecimiento_id')" />
    </div>
</div>
