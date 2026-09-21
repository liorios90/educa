<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Resultado de la importación
            </h2>
            <a href="{{ route('Admin.padres') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Volver a padres
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'representantes-imported')
                <p class="mb-4 text-sm font-medium text-green-700">La importación se procesó. Revisa el detalle de cada fila.</p>
            @endif

            <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-800">{{ $importData->mensaje }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    Archivo: {{ $importData->nombre }} ({{ $importData->tipo_archivo }})
                </p>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-6 py-3 font-medium">Fila</th>
                            <th class="px-6 py-3 font-medium">Identificación</th>
                            <th class="px-6 py-3 font-medium">Estado</th>
                            <th class="px-6 py-3 font-medium">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse ($importData->detalles as $detalle)
                            <tr>
                                <td class="px-6 py-3">{{ $detalle->num_fila ?? '—' }}</td>
                                <td class="px-6 py-3">{{ $detalle->identificacion ?? '—' }}</td>
                                <td class="px-6 py-3">
                                    @if ($detalle->wasImported())
                                        <span class="font-medium text-green-700">Importado</span>
                                    @else
                                        <span class="font-medium text-red-700">Fallido</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3">{{ $detalle->descripcion }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                                    No hay detalle de filas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
