<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Editar usuario
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route($usersIndexRoute.'.update', $user) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @method('patch')

                <div>
                    <x-input-label for="name" value="Nombre" />
                    <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-input-label for="email" value="Correo" />
                    <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email', $user->email)" required autocomplete="username" />
                    <x-input-error class="mt-2" :messages="$errors->get('email')" />
                </div>

                <div>
                    <x-input-label for="password" value="Nueva contraseña (opcional)" />
                    <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" autocomplete="new-password" />
                    <x-input-error class="mt-2" :messages="$errors->get('password')" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                    <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" autocomplete="new-password" />
                </div>

                <div>
                    <x-input-label for="role" value="Rol" />
                    <select
                        id="role"
                        name="role"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        @php
                            $selectedRole = old('role', $user->roles->first()?->name);
                        @endphp
                        <option value="" disabled @selected($selectedRole === null)>Selecciona un rol</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected($selectedRole === $role->value)>
                                {{ $role->label() }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('role')" />
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route($usersIndexRoute) }}" class="text-sm text-slate-600 hover:text-slate-900">Cancelar</a>
                    <x-primary-button>Guardar cambios</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
