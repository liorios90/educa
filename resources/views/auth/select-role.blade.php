<x-guest-layout>
    <div class="space-y-6">
        <div class="text-center">
            <h1 class="text-lg font-semibold text-slate-900">¿Con qué rol quieres entrar?</h1>
            <p class="mt-2 text-sm text-slate-500">Tu usuario tiene más de un rol. Elige cómo usar el sistema en esta sesión.</p>
        </div>

        <form method="POST" action="{{ route('role.store') }}" class="flex flex-col gap-3">
            @csrf

            @foreach ($roles as $role)
                <button
                    type="submit"
                    name="role"
                    value="{{ $role->value }}"
                    class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-left shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 {{ $activeRole === $role ? 'border-indigo-400 ring-1 ring-indigo-200' : '' }}"
                >
                    <span class="text-sm font-semibold text-slate-800">{{ $role->label() }}</span>
                    <span class="text-xs text-slate-400">Entrar</span>
                </button>
            @endforeach

            <x-input-error :messages="$errors->get('role')" class="mt-1" />
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="text-sm text-slate-500 underline hover:text-slate-800">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
