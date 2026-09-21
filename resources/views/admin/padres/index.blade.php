<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Padres de familia
            </h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('Admin.padres.import') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Importar Excel
                </a>
                <a href="{{ route('Admin.padres.create') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    Nuevo padre
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'padre-created')
                <p class="mb-4 text-sm font-medium text-green-700">Padre creado correctamente.</p>
            @endif

            @if (session('status') === 'padre-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Padre actualizado correctamente.</p>
            @endif

            @if (session('status') === 'padre-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Padre eliminado correctamente.</p>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-6 py-3 font-medium">Apellidos</th>
                            <th class="px-6 py-3 font-medium">Nombres</th>
                            <th class="px-6 py-3 font-medium">Identificación</th>
                            <th class="px-6 py-3 font-medium">Correo</th>
                            <th class="px-6 py-3 font-medium">Teléfono</th>
                            <th class="px-6 py-3 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse ($padres as $padre)
                            <tr>
                                <td class="px-6 py-3">{{ $padre->persona?->apellidos }}</td>
                                <td class="px-6 py-3">{{ $padre->persona?->nombres }}</td>
                                <td class="px-6 py-3">{{ $padre->persona?->identificacion }}</td>
                                <td class="px-6 py-3">{{ $padre->persona?->user?->email ?? '—' }}</td>
                                <td class="px-6 py-3">{{ $padre->persona?->telefono1 }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('Admin.padres.edit', $padre) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('Admin.padres.destroy', $padre) }}" onsubmit="return confirm('¿Eliminar este padre de familia?')">
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
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                    Aún no hay padres de familia.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
