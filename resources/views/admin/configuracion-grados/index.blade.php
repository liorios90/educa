<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Configuración por grados</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $periodo?->nombre ?? 'Sin periodo activo' }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'configuracion-grados-created')
                <p class="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">La configuración por grados se creó correctamente.</p>
            @elseif (session('status') === 'configuracion-grados-updated')
                <p class="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">La configuración por grados se actualizó correctamente.</p>
            @elseif (session('status') === 'configuracion-grados-deleted')
                <p class="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">La configuración por grados se eliminó correctamente.</p>
            @endif

            @if ($periodo === null)
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">Selecciona una oferta con un periodo activo para administrar estas configuraciones.</p>
            @else
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Configuraciones creadas</h3>
                            <p class="mt-1 text-sm text-slate-600">Cada grado puede pertenecer a una sola configuración del periodo.</p>
                        </div>
                        @if (! $periodo->estaAbierto() && $periodo->ciclos->isNotEmpty())
                            <a href="{{ route('Admin.configuracion-grados.create') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Nueva configuración</a>
                        @endif
                    </div>

                    @if ($periodo->estaAbierto())
                        <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">El periodo ya fue aperturado. Las configuraciones se pueden consultar, pero no modificar.</p>
                    @elseif ($periodo->ciclos->isEmpty())
                        <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Primero guarda los ciclos en la configuración del periodo.</p>
                    @endif

                    @if ($configuraciones->isEmpty())
                        <p class="mt-6 rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">Todavía no hay configuraciones por grados para este periodo.</p>
                    @else
                        <div class="mt-6 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr><th class="px-4 py-3 font-semibold">Nombre</th><th class="px-4 py-3 font-semibold">Grados</th><th class="px-4 py-3 text-right font-semibold">Acciones</th></tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($configuraciones as $configuracion)
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-slate-900">{{ $configuracion->nombre }}</td>
                                            <td class="px-4 py-3 text-slate-600">{{ $configuracion->grados->pluck('nombre')->implode(', ') }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="inline-flex items-center gap-3">
                                                    <a href="{{ route('Admin.configuracion-grados.edit', $configuracion) }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ $periodo->estaAbierto() ? 'Consultar' : 'Editar' }}</a>
                                                    @if (! $periodo->estaAbierto())
                                                        <form method="POST" action="{{ route('Admin.configuracion-grados.destroy', $configuracion) }}" onsubmit="return confirm('¿Eliminar esta configuración por grados?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="font-medium text-red-600 hover:text-red-800">Eliminar</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
