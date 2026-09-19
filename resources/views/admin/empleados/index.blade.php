<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Empleados
            </h2>
            <a href="{{ route('Admin.empleados.create') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Nuevo empleado
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'empleado-created')
                <p class="mb-4 text-sm font-medium text-green-700">Empleado creado correctamente.</p>
            @endif

            @if (session('status') === 'empleado-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Empleado actualizado correctamente.</p>
            @endif

            @if (session('status') === 'empleado-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Empleado eliminado correctamente.</p>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-6 py-3 font-medium">Apellidos</th>
                            <th class="px-6 py-3 font-medium">Nombres</th>
                            <th class="px-6 py-3 font-medium">Identificación</th>
                            <th class="px-6 py-3 font-medium">Tipo de contrato</th>
                            <th class="px-6 py-3 font-medium">Función</th>
                            <th class="px-6 py-3 font-medium">Roles</th>
                            <th class="px-6 py-3 font-medium">Correo</th>
                            <th class="px-6 py-3 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse ($empleados as $empleado)
                            <tr>
                                <td class="px-6 py-3">{{ $empleado->persona?->apellidos }}</td>
                                <td class="px-6 py-3">{{ $empleado->persona?->nombres }}</td>
                                <td class="px-6 py-3">{{ $empleado->persona?->identificacion }}</td>
                                <td class="px-6 py-3">{{ $empleado->tipoContrato?->nombre ?? '—' }}</td>
                                <td class="px-6 py-3">{{ $empleado->funcion?->nombre ?? '—' }}</td>
                                <td class="px-6 py-3">
                                    {{ $empleado->persona?->user?->roles->map(fn ($role) => \App\Enums\Role::tryFrom($role->name)?->label() ?? $role->name)->join(', ') ?: '—' }}
                                </td>
                                <td class="px-6 py-3">{{ $empleado->persona?->user?->email ?? '—' }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('Admin.empleados.edit', $empleado) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('Admin.empleados.destroy', $empleado) }}" onsubmit="return confirm('¿Eliminar este empleado?')">
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
                                <td colspan="8" class="px-6 py-8 text-center text-slate-500">
                                    Aún no hay empleados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
