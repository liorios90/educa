<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">Consultas de la IA</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'consulta-eliminada')
                <p class="mb-4 text-sm font-medium text-green-700">Consulta eliminada correctamente.</p>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-6 py-3 font-medium">Fecha y hora</th>
                            <th class="px-6 py-3 font-medium">Usuario</th>
                            <th class="px-6 py-3 font-medium">Instituto</th>
                            <th class="w-80 px-6 py-3 font-medium">Pregunta</th>
                            <th class="px-6 py-3 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse ($consultas as $consulta)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-3 text-slate-600">{{ $consulta->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3">{{ $consulta->usuario }}</td>
                                <td class="px-6 py-3">{{ $consulta->establecimiento?->nombre }}</td>
                                <td class="w-80 max-w-xs px-6 py-3">
                                    <button
                                        type="button"
                                        class="block w-full overflow-hidden text-left font-medium text-indigo-600 hover:text-indigo-500"
                                        style="display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3;"
                                        x-on:click="$dispatch('open-modal', 'consulta-{{ $consulta->id }}')"
                                    >
                                        {{ $consulta->pregunta }}
                                    </button>
                                </td>
                                <td class="whitespace-nowrap px-6 py-3">
                                    <form method="POST" action="{{ route('sistemas.asistente-consultas.destroy', $consulta) }}" onsubmit="return confirm('¿Eliminar esta consulta?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="font-medium text-red-600 hover:text-red-500">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500">Aún no hay consultas a la IA.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @foreach ($consultas as $consulta)
                <x-modal :name="'consulta-'.$consulta->id" maxWidth="2xl">
                    <div class="space-y-4 p-6">
                        <h3 class="text-lg font-semibold text-slate-800">Consulta de la IA</h3>
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="font-medium text-slate-500">Fecha y hora</dt>
                                <dd class="mt-1 text-slate-800">{{ $consulta->created_at?->format('d/m/Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Usuario</dt>
                                <dd class="mt-1 text-slate-800">{{ $consulta->usuario }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Instituto</dt>
                                <dd class="mt-1 text-slate-800">{{ $consulta->establecimiento?->nombre }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Pregunta</dt>
                                <dd class="mt-1 whitespace-pre-wrap break-words text-slate-800">{{ $consulta->pregunta }}</dd>
                            </div>
                        </dl>
                        <div class="flex flex-wrap items-center justify-end gap-3">
                            <form method="POST" action="{{ route('sistemas.asistente-consultas.destroy', $consulta) }}" onsubmit="return confirm('¿Eliminar esta consulta?')">
                                @csrf
                                @method('delete')
                                <x-danger-button>Eliminar</x-danger-button>
                            </form>
                            <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'consulta-{{ $consulta->id }}')">
                                Cerrar
                            </x-secondary-button>
                        </div>
                    </div>
                </x-modal>
            @endforeach

            @if ($consultas->hasPages())
                <div class="mt-4">
                    {{ $consultas->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
