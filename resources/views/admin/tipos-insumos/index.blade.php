<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Tipos de Insumo</h2>
            <a href="{{ route('Admin.tipos-insumos.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Nuevo tipo de Insumo</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'tipos-insumos-created')
                <p class="mb-4 text-sm font-medium text-green-700">Tipo de Insumo creado correctamente.</p>
            @elseif (session('status') === 'tipos-insumos-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Tipo de Insumo actualizado correctamente.</p>
            @elseif (session('status') === 'tipos-insumos-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Tipo de Insumo eliminado correctamente.</p>
            @endif

            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-6 py-3 font-medium">Nombre</th>
                            <th class="px-6 py-3 font-medium">Descripción</th>
                            <th class="px-6 py-3 font-medium">Tipo de calificación</th>
                            <th class="px-6 py-3 font-medium">Activo</th>
                            <th class="px-6 py-3 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse ($tiposInsumos as $tipoInsumo)
                            <tr>
                                <td class="px-6 py-3 font-medium">{{ $tipoInsumo->nombre }}</td>
                                <td class="px-6 py-3 text-slate-600">{{ $tipoInsumo->descripcion ?: '—' }}</td>
                                <td class="px-6 py-3">{{ \App\Enums\TipoCalificacion::tryFrom($tipoInsumo->tipo_calificacion)?->label() ?? $tipoInsumo->tipo_calificacion }}</td>
                                <td class="px-6 py-3">{{ $tipoInsumo->activo ? 'Sí' : 'No' }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('Admin.tipos-insumos.asignaturas.index', $tipoInsumo) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Asignaturas</a>
                                        <a href="{{ route('Admin.tipos-insumos.detalles.index', $tipoInsumo) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Detalle</a>
                                        <a href="{{ route('Admin.tipos-insumos.edit', $tipoInsumo) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Modificar</a>
                                        <form method="POST" action="{{ route('Admin.tipos-insumos.destroy', $tipoInsumo) }}" onsubmit="return confirm('¿Eliminar este tipo de Insumo?')">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="font-medium text-red-600 hover:text-red-800">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">Aún no hay tipos de Insumo creados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
