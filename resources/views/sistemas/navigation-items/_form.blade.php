@php
    $item = $item ?? null;
    $asSubmenu = $asSubmenu ?? false;
    $selectedRoles = collect(old('roles', $item?->roles->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id);
    $visibleToAll = (bool) old('visible_to_all', $item?->visible_to_all ?? false);
    $isGroup = (bool) old('is_group', $item?->is_group ?? false);
    $groupDisplay = old('group_display', $item?->group_display?->value ?? \App\Enums\NavigationGroupDisplay::Screen->value);
    $selectedIcon = old('icon', $item?->icon ?? 'home');
@endphp

<div
    class="space-y-6"
    x-data="{ visibleToAll: @js($visibleToAll), isGroup: @js($isGroup), groupDisplay: @js($groupDisplay) }"
>
    <div>
        <x-input-label for="label" value="Texto del menú" />
        <x-text-input id="label" class="mt-1 block w-full" type="text" name="label" :value="old('label', $item?->label)" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('label')" />
    </div>

    @unless ($asSubmenu)
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="hidden" name="is_group" value="0">
            <input
                id="is_group"
                type="checkbox"
                name="is_group"
                value="1"
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                @checked($isGroup)
                x-model="isGroup"
            >
            Es un menú con submenús
        </label>
        <x-input-error class="mt-2" :messages="$errors->get('is_group')" />

        <fieldset x-show="isGroup" x-cloak>
            <legend class="block text-sm font-medium text-slate-700">Cómo se presenta</legend>
            <div class="mt-3 flex flex-col gap-2">
                @foreach (\App\Enums\NavigationGroupDisplay::cases() as $display)
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input
                            type="radio"
                            name="group_display"
                            value="{{ $display->value }}"
                            class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            x-model="groupDisplay"
                            @checked($groupDisplay === $display->value)
                        >
                        {{ $display->label() }}
                    </label>
                @endforeach
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('group_display')" />
        </fieldset>
    @endunless

    <div x-show="{{ $asSubmenu ? 'true' : '! isGroup' }}" x-cloak>
        <x-input-label for="route_name" value="Ruta" />
        <select
            id="route_name"
            name="route_name"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            @unless ($asSubmenu)
                x-bind:required="! isGroup"
            @else
                required
            @endunless
        >
            <option value="" disabled @selected(old('route_name', $item?->route_name) === null || old('route_name', $item?->route_name) === 'navigation.hub')>Selecciona una ruta</option>
            @foreach ($routeNames as $routeName)
                <option value="{{ $routeName }}" @selected(old('route_name', $item?->route_name) === $routeName)>
                    {{ $routeName }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('route_name')" />
    </div>

    <div x-data="{ selectedIcon: @js($selectedIcon), iconQuery: '' }">
        <x-input-label value="Icono" />
        <x-text-input
            id="icon-search"
            class="mt-2 block w-full"
            type="search"
            x-model="iconQuery"
            placeholder="Buscar icono"
            aria-label="Buscar icono"
            autocomplete="off"
        />
        <div class="mt-3 grid max-h-72 grid-cols-3 gap-2 overflow-y-auto sm:grid-cols-4" role="radiogroup" aria-label="Icono">
            @foreach ($icons as $name => $icon)
                <label
                    class="flex cursor-pointer flex-col items-center gap-1 rounded-xl border px-2 py-2.5 text-center transition"
                    :class="selectedIcon === '{{ $name }}'
                        ? 'border-indigo-500 bg-indigo-50 text-indigo-800 ring-1 ring-indigo-500'
                        : 'border-slate-200 bg-white text-slate-700 hover:border-indigo-300 hover:bg-slate-50'"
                    x-show="iconQuery.trim() === '' || '{{ mb_strtolower($icon['label'].' '.$name) }}'.includes(iconQuery.trim().toLowerCase())"
                >
                    <input
                        class="sr-only"
                        type="radio"
                        name="icon"
                        value="{{ $name }}"
                        x-model="selectedIcon"
                        @checked($selectedIcon === $name)
                    >
                    <x-sidebar-icon :name="$name" />
                    <span class="text-xs font-medium leading-tight">{{ $icon['label'] }}</span>
                </label>
            @endforeach
        </div>
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

    @unless ($asSubmenu)
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
    @endunless
</div>
