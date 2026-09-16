<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Establecimientos
            </h2>
            <a href="{{ route('sistemas.establecimientos.create') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Nuevo establecimiento
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'establecimiento-created')
                <p class="mb-4 text-sm font-medium text-green-700">Establecimiento creado correctamente.</p>
            @endif

            @if (session('status') === 'establecimiento-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Establecimiento actualizado correctamente.</p>
            @endif

            @if (session('status') === 'establecimiento-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Establecimiento eliminado correctamente.</p>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-6 py-3 font-medium">Nombre</th>
                            <th class="px-6 py-3 font-medium">AMIE</th>
                            <th class="px-6 py-3 font-medium">Correo</th>
                            <th class="px-6 py-3 font-medium">Zona</th>
                            <th class="px-6 py-3 font-medium">Distrito</th>
                            <th class="px-6 py-3 font-medium">Circuito</th>
                            <th class="px-6 py-3 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse ($establecimientos as $establecimiento)
                            <tr>
                                <td class="px-6 py-3">{{ $establecimiento->nombre }}</td>
                                <td class="px-6 py-3">{{ $establecimiento->codigo_amie }}</td>
                                <td class="px-6 py-3">{{ $establecimiento->email }}</td>
                                <td class="px-6 py-3">{{ $establecimiento->zona?->nombre ?? '—' }}</td>
                                <td class="px-6 py-3">{{ $establecimiento->distrito?->nombre ?? '—' }}</td>
                                <td class="px-6 py-3">{{ $establecimiento->circuito?->nombre ?? '—' }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('sistemas.establecimientos.edit', $establecimiento) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('sistemas.establecimientos.destroy', $establecimiento) }}" onsubmit="return confirm('¿Eliminar este establecimiento?')">
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
                                    Aún no hay establecimientos.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
