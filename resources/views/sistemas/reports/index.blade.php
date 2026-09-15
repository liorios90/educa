<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Diseñar reportes
            </h2>
            <a href="{{ route('sistemas.reports.create') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Nuevo reporte
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'report-created')
                <p class="mb-4 text-sm font-medium text-green-700">Reporte guardado correctamente.</p>
            @endif
            @if (session('status') === 'report-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Reporte actualizado correctamente.</p>
            @endif
            @if (session('status') === 'report-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Reporte eliminado correctamente.</p>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-6 py-3 font-medium">Nombre</th>
                            <th class="px-6 py-3 font-medium">Tabla</th>
                            <th class="px-6 py-3 font-medium">Campos</th>
                            <th class="px-6 py-3 font-medium">Visible para</th>
                            <th class="px-6 py-3 font-medium">Estado</th>
                            <th class="px-6 py-3 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse ($reports as $item)
                            <tr>
                                <td class="px-6 py-3">{{ $item->name }}</td>
                                <td class="px-6 py-3">{{ $item->source }}</td>
                                <td class="px-6 py-3">{{ $item->fields_count }}</td>
                                <td class="px-6 py-3">
                                    @if ($item->visible_to_all)
                                        Todos
                                    @else
                                        {{ $item->roles->map(fn ($role) => \App\Enums\Role::tryFrom($role->name)?->label() ?? $role->name)->join(', ') ?: 'Sin roles' }}
                                    @endif
                                </td>
                                <td class="px-6 py-3">{{ $item->is_active ? 'Activo' : 'Oculto' }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('reports.show', $item) }}" class="font-medium text-indigo-600 hover:text-indigo-500">Ver</a>
                                        <a href="{{ route('sistemas.reports.edit', $item) }}" class="font-medium text-indigo-600 hover:text-indigo-500">Editar</a>
                                        <form method="POST" action="{{ route('sistemas.reports.destroy', $item) }}" onsubmit="return confirm('¿Eliminar este reporte?')">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="font-medium text-red-600 hover:text-red-500">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">Aún no hay reportes. Crea uno y elige los campos.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
