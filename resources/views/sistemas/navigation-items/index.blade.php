<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Opciones de menú
            </h2>
            <a href="{{ route('sistemas.navigation-items.create') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Nueva opción
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'navigation-item-created')
                <p class="mb-4 text-sm font-medium text-green-700">Opción de menú creada correctamente.</p>
            @endif

            @if (session('status') === 'navigation-item-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Opción de menú actualizada correctamente.</p>
            @endif

            @if (session('status') === 'navigation-item-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Opción de menú eliminada correctamente.</p>
            @endif

            <form method="GET" action="{{ route('sistemas.navigation-items.index') }}" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <x-text-input
                    id="q"
                    class="block w-full sm:max-w-sm"
                    type="search"
                    name="q"
                    :value="$search"
                    placeholder="Buscar por texto, ruta o rol"
                    aria-label="Buscar opciones de menú"
                />
                <div class="flex items-center gap-3">
                    <x-primary-button>Buscar</x-primary-button>
                    @if ($search !== '')
                        <a href="{{ route('sistemas.navigation-items.index', ['sort' => $sort, 'direction' => $direction]) }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <x-sortable-column field="sort_order" :current="$sort" :direction="$direction" route="sistemas.navigation-items.index">Orden</x-sortable-column>
                            <x-sortable-column field="label" :current="$sort" :direction="$direction" route="sistemas.navigation-items.index">Texto</x-sortable-column>
                            <x-sortable-column field="route_name" :current="$sort" :direction="$direction" route="sistemas.navigation-items.index">Ruta</x-sortable-column>
                            <!-- <x-sortable-column field="is_group" :current="$sort" :direction="$direction" route="sistemas.navigation-items.index">Tipo</x-sortable-column> -->
                            <x-sortable-column field="visible_to_all" :current="$sort" :direction="$direction" route="sistemas.navigation-items.index">Visible para</x-sortable-column>
                            <x-sortable-column field="is_active" :current="$sort" :direction="$direction" route="sistemas.navigation-items.index">Estado</x-sortable-column>
                            <th class="px-6 py-3 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse ($items as $item)
                            <tr>
                                <td class="px-6 py-3">{{ $item->sort_order }}</td>
                                <td class="px-6 py-3">{{ $item->label }}</td>
                                <td class="px-6 py-3">{{ $item->is_group ? 'Botones' : $item->route_name }}</td>
                                <!-- <td class="px-6 py-3">{{ $item->is_group ? 'Grupo' : 'Enlace' }}</td> -->
                                <td class="px-6 py-3">
                                    @if ($item->visible_to_all)
                                        Todos
                                    @else
                                        {{ $item->roles->map(fn ($role) => \App\Enums\Role::tryFrom($role->name)?->label() ?? $role->name)->join(', ') ?: 'Sin roles' }}
                                    @endif
                                </td>
                                <td class="px-6 py-3">{{ $item->is_active ? 'Activa' : 'Oculta' }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($item->is_group)
                                            <a href="{{ route('sistemas.navigation-items.submenus.index', $item) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                                Submenús
                                            </a>
                                        @endif
                                        <a href="{{ route('sistemas.navigation-items.edit', $item) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('sistemas.navigation-items.destroy', $item) }}" onsubmit="return confirm('¿Eliminar esta opción de menú?')">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="font-medium text-red-600 hover:text-red-500">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                                    @if ($search !== '')
                                        No hay opciones que coincidan con «{{ $search }}».
                                    @else
                                        Aún no hay opciones de menú.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="mt-4">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
