<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                {{ $definition->title }}
            </h2>
            <a href="{{ route($definition->routeName('create')) }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Nueva {{ $definition->singular }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'crud-created')
                <p class="mb-4 text-sm font-medium text-green-700">Registro creado correctamente.</p>
            @endif

            @if (session('status') === 'crud-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Registro actualizado correctamente.</p>
            @endif

            @if (session('status') === 'crud-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Registro eliminado correctamente.</p>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            @foreach ($definition->listFields() as $field)
                                <th class="px-6 py-3 font-medium">{{ $field->label }}</th>
                            @endforeach
                            <th class="px-6 py-3 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse ($records as $record)
                            <tr>
                                @foreach ($definition->listFields() as $field)
                                    <td class="px-6 py-3">{{ $definition->displayValue($record, $field) }}</td>
                                @endforeach
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route($definition->routeName('edit'), $record->getKey()) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route($definition->routeName('destroy'), $record->getKey()) }}" onsubmit="return confirm('¿Eliminar este registro?')">
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
                                <td colspan="{{ count($definition->listFields()) + 1 }}" class="px-6 py-8 text-center text-slate-500">
                                    Aún no hay registros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($backUrl)
                <div class="mt-6">
                    <a
                        href="{{ $backUrl }}"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        Volver
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
