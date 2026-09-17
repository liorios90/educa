@php
    $roleLabel = $activeRole?->label()
        ?? ($user->roles->first() ? (\App\Enums\Role::tryFrom($user->roles->first()->name)?->label() ?? $user->roles->first()->name) : 'Sin rol');
@endphp

<div
    x-show="sidebarOpen"
    x-transition.opacity
    @click="sidebarOpen = false"
    class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden"
    style="display: none;"
></div>

<aside
    {{ $attributes->merge(['class' => 'fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-slate-950 text-slate-200 shadow-xl transition-transform duration-200 ease-out lg:translate-x-0']) }}
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="flex h-16 items-center gap-3 px-6">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 text-white">
            @if ($isSistemas)
                <span class="truncate text-base font-semibold tracking-tight">{{ config('app.name', 'Educa') }}</span>
            @elseif ($establecimiento)
                <span class="truncate text-base font-semibold tracking-tight">{{ $establecimiento->nombre }}</span>
            @else
                <x-application-logo class="h-8 w-8 fill-current text-indigo-300" />
                <span class="truncate text-base font-semibold tracking-tight">{{ config('app.name', 'Educa') }}</span>
            @endif
        </a>
    </div>

    <nav class="flex flex-1 flex-col gap-1 px-4 py-4" aria-label="Menú principal">
        @foreach ($items as $item)
            <x-sidebar-link :href="$item->url()" :active="$item->isActive()" :icon="$item->icon">
                {{ $item->label }}
            </x-sidebar-link>
        @endforeach
    </nav>

    <div class="mt-auto flex flex-col gap-3 border-t border-white/10 px-4 py-4">
        <div class="flex items-center gap-3 rounded-xl px-2 py-1">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-500/20 text-sm font-semibold text-indigo-200">
                {{ strtoupper(mb_substr($user->name, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-white">{{ $user->name }}</p>
                <p class="truncate text-xs text-slate-400">{{ $roleLabel }}</p>
            </div>
        </div>

        @if ($canSwitchRole)
            <a href="{{ route('role.select') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white">
                Cambiar rol
            </a>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white">
                <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                </svg>
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>
