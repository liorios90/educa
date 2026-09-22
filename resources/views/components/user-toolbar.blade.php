<div {{ $attributes->merge(['class' => 'flex shrink-0 items-center gap-3']) }}>
    <div class="min-w-0 max-w-[9rem] text-right sm:max-w-xs">
        <p class="truncate text-xs font-semibold uppercase tracking-wide text-slate-800">{{ $user->name }}</p>
        <p class="truncate text-[11px] text-slate-500">{{ $roleLabel }}</p>
    </div>

    <div class="flex items-center gap-0.5">
        <a
            href="{{ route('profile.edit') }}"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-indigo-700"
            title="Perfil"
        >
            <span class="sr-only">Perfil</span>
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
        </a>

        @if ($canSwitchRole)
            <a
                href="{{ route('role.select') }}"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-indigo-700"
                title="Cambiar rol"
            >
                <span class="sr-only">Cambiar rol</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
            </a>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-red-600"
                title="Cerrar sesión"
            >
                <span class="sr-only">Cerrar sesión</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" />
                </svg>
            </button>
        </form>
    </div>
</div>
