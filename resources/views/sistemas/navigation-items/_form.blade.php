@php
    $item = $item ?? null;
    $selectedRoles = collect(old('roles', $item?->roles->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id);
    $visibleToAll = (bool) old('visible_to_all', $item?->visible_to_all ?? false);
@endphp

<div
    class="space-y-6"
    x-data="{ visibleToAll: @js($visibleToAll) }"
>
    <div>
        <x-input-label for="label" value="Texto del menú" />
        <x-text-input id="label" class="mt-1 block w-full" type="text" name="label" :value="old('label', $item?->label)" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('label')" />
    </div>

    <div>
        <x-input-label for="route_name" value="Ruta" />
        <select
            id="route_name"
            name="route_name"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            required
        >
            <option value="" disabled @selected(old('route_name', $item?->route_name) === null)>Selecciona una ruta</option>
            @foreach ($routeNames as $routeName)
                <option value="{{ $routeName }}" @selected(old('route_name', $item?->route_name) === $routeName)>
                    {{ $routeName }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('route_name')" />
    </div>

    <div>
        <x-input-label for="icon" value="Icono" />
        <select
            id="icon"
            name="icon"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            required
        >
            @foreach ($icons as $icon)
                <option value="{{ $icon }}" @selected(old('icon', $item?->icon ?? 'home') === $icon)>
                    {{ $icon }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('icon')" />
    </div>

    <div>
        <x-input-label for="sort_order" value="Orden" />
        <x-text-input id="sort_order" class="mt-1 block w-full" type="number" min="0" name="sort_order" :value="old('sort_order', $item?->sort_order ?? 0)" required />
        <x-input-error class="mt-2" :messages="$errors->get('sort_order')" />
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="hidden" name="is_active" value="0">
        <input
            id="is_active"
            type="checkbox"
            name="is_active"
            value="1"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
            @checked(old('is_active', $item?->is_active ?? true))
        >
        Visible en el menú
    </label>

    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="hidden" name="visible_to_all" value="0">
        <input
            id="visible_to_all"
            type="checkbox"
            name="visible_to_all"
            value="1"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
            @checked($visibleToAll)
            x-model="visibleToAll"
        >
        Visible para todos los usuarios autenticados
    </label>

    <div x-show="! visibleToAll" x-cloak>
        <p class="mb-2 text-sm font-medium text-gray-700">Roles</p>
        <div class="flex flex-col gap-2">
            @foreach ($roles as $role)
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        name="roles[]"
                        value="{{ $role->id }}"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        @checked($selectedRoles->contains($role->id))
                    >
                    {{ \App\Enums\Role::tryFrom($role->name)?->label() ?? $role->name }}
                </label>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('roles')" />
    </div>
</div>
