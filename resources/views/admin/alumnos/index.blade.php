<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Alumnos
            </h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('Admin.padres.import', ['tipo' => 'alumnos']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Importar Excel
                </a>
                <a href="{{ route('Admin.alumnos.create') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    Nuevo alumno
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'alumno-created')
                <p class="mb-4 text-sm font-medium text-green-700">Alumno creado correctamente.</p>
            @endif

            @if (session('status') === 'alumno-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Alumno actualizado correctamente.</p>
            @endif

            @if (session('status') === 'alumno-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Alumno eliminado correctamente.</p>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-6 py-3 font-medium">Apellidos</th>
                            <th class="px-6 py-3 font-medium">Nombres</th>
                            <th class="px-6 py-3 font-medium">Identificación</th>
                            <th class="px-6 py-3 font-medium">Padre</th>
                            <th class="px-6 py-3 font-medium">Correo</th>
                            <th class="px-6 py-3 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse ($alumnos as $alumno)
                            <tr>
                                <td class="px-6 py-3">{{ $alumno->persona?->apellidos }}</td>
                                <td class="px-6 py-3">{{ $alumno->persona?->nombres }}</td>
                                <td class="px-6 py-3">{{ $alumno->persona?->identificacion }}</td>
                                <td class="px-6 py-3">
                                    {{ $alumno->padre?->persona ? trim($alumno->padre->persona->apellidos.' '.$alumno->padre->persona->nombres) : '—' }}
                                </td>
                                <td class="px-6 py-3">{{ $alumno->persona?->user?->email ?? '—' }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('Admin.alumnos.edit', $alumno) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('Admin.alumnos.destroy', $alumno) }}" onsubmit="return confirm('¿Eliminar este alumno?')">
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
                                    Aún no hay alumnos.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
