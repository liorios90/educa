<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Usuarios
            </h2>
            <a href="{{ route($usersIndexRoute.'.create') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Nuevo usuario
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'user-created')
                <p class="mb-4 text-sm font-medium text-green-700">Usuario creado correctamente.</p>
            @endif

            @if (session('status') === 'user-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Usuario actualizado correctamente.</p>
            @endif

            @if (session('status') === 'user-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Usuario eliminado correctamente.</p>
            @endif

            @if (session('status') === 'user-self-delete-blocked')
                <p class="mb-4 text-sm font-medium text-amber-700">No puedes eliminar tu propio usuario.</p>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-6 py-3 font-medium">Nombre</th>
                            <th class="px-6 py-3 font-medium">Correo</th>
                            <th class="px-6 py-3 font-medium">Rol</th>
                            <th class="px-6 py-3 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-6 py-3">{{ $user->name }}</td>
                                <td class="px-6 py-3">{{ $user->email }}</td>
                                <td class="px-6 py-3">
                                    {{ $user->roles->map(fn ($role) => \App\Enums\Role::tryFrom($role->name)?->label() ?? $role->name)->join(', ') ?: 'Sin rol' }}
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route($usersIndexRoute.'.edit', $user) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                            Editar
                                        </a>

                                        @unless (auth()->user()->is($user))
                                            <form method="POST" action="{{ route($usersIndexRoute.'.destroy', $user) }}" onsubmit="return confirm('¿Eliminar este usuario?')">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="font-medium text-red-600 hover:text-red-500">
                                                    Eliminar
                                                </button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-500">Aún no hay usuarios.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
