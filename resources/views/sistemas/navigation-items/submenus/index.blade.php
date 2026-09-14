<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Submenús de {{ $parent->label }}
            </h2>
            <a href="{{ route('sistemas.navigation-items.submenus.create', $parent) }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Nuevo submenú
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'navigation-submenu-created')
                <p class="mb-4 text-sm font-medium text-green-700">Submenú creado correctamente.</p>
            @endif

            @if (session('status') === 'navigation-submenu-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Submenú actualizado correctamente.</p>
            @endif

            @if (session('status') === 'navigation-submenu-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Submenú eliminado correctamente.</p>
            @endif

            <p class="mb-4">
                <a href="{{ route('sistemas.navigation-items.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Volver al menú</a>
            </p>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-6 py-3 font-medium">Orden</th>
                            <th class="px-6 py-3 font-medium">Texto</th>
                            <th class="px-6 py-3 font-medium">Ruta</th>
                            <th class="px-6 py-3 font-medium">Estado</th>
                            <th class="px-6 py-3 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse ($items as $item)
                            <tr>
                                <td class="px-6 py-3">{{ $item->sort_order }}</td>
                                <td class="px-6 py-3">{{ $item->label }}</td>
                                <td class="px-6 py-3">{{ $item->route_name }}</td>
                                <td class="px-6 py-3">{{ $item->is_active ? 'Activo' : 'Oculto' }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('sistemas.navigation-items.submenus.edit', [$parent, $item]) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('sistemas.navigation-items.submenus.destroy', [$parent, $item]) }}" onsubmit="return confirm('¿Eliminar este submenú?')">
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
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500">Aún no hay submenús. Se mostrarán como botones al abrir este menú.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
