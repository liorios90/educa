<div
    x-show="sidebarOpen"
    x-transition.opacity
    @click="sidebarOpen = false"
    class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden"
    style="display: none;"
></div>

<aside
    id="app-sidebar"
    {{ $attributes->merge(['class' => 'fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-slate-950 text-slate-200 shadow-xl transition-transform duration-200 ease-out lg:translate-x-0']) }}
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    :style="sidebarOpen ? { transform: 'translateX(0px)' } : { transform: 'translateX(-100%)' }"
>
    <div class="flex h-16 items-center gap-2 px-3">
        <button
            type="button"
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-300 transition hover:bg-white/5 hover:text-white"
            @click="sidebarOpen = false"
            title="Cerrar menú"
            aria-controls="app-sidebar"
            :aria-expanded="sidebarOpen.toString()"
        >
            <span class="sr-only">Cerrar menú</span>
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m18.75 4.5-7.5 7.5 7.5 7.5m-6-15L5.25 12l7.5 7.5" />
            </svg>
        </button>

        <a href="{{ route('dashboard') }}" class="flex min-w-0 flex-1 items-center gap-3 text-white">
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

    <nav class="flex min-h-0 flex-1 flex-col gap-1 overflow-y-auto px-4 py-4" aria-label="Menú principal">
        @foreach ($items as $item)
            @if ($item->showsChildrenInSidebar())
                <div x-data="{ open: {{ $item->isActive() ? 'true' : 'false' }} }" class="flex flex-col gap-1">
                    <button
                        type="button"
                        class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition {{ $item->isActive() ? 'bg-white/10 text-white shadow-sm ring-1 ring-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                        @click="open = ! open"
                        :aria-expanded="open.toString()"
                    >
                        <x-sidebar-icon :name="$item->icon" class="{{ $item->isActive() ? 'text-indigo-300' : 'text-slate-400 group-hover:text-slate-200' }}" />
                        <span class="min-w-0 flex-1 truncate">{{ $item->label }}</span>
                        <svg
                            class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200"
                            :class="open ? 'rotate-90' : ''"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak class="flex flex-col gap-1 border-l border-white/10 ml-5 pl-2">
                        @forelse ($item->children as $child)
                            <x-sidebar-link :href="$child->url()" :active="$child->isActive()" :icon="$child->icon" :nested="true">
                                {{ $child->label }}
                            </x-sidebar-link>
                        @empty
                            <p class="px-3 py-2 text-xs text-slate-500">Sin opciones</p>
                        @endforelse
                    </div>
                </div>
            @else
                <x-sidebar-link :href="$item->url()" :active="$item->isActive()" :icon="$item->icon">
                    {{ $item->label }}
                </x-sidebar-link>
            @endif
        @endforeach
    </nav>
</aside>
