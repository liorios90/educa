@props([
    'href',
    'label',
    'icon' => 'squares-2x2',
    'description' => null,
])

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'group flex h-full items-start gap-4 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm ring-1 ring-slate-900/5 transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md']) }}
>
    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100 transition group-hover:bg-indigo-600 group-hover:text-white group-hover:ring-indigo-600">
        <x-sidebar-icon :name="$icon" />
    </span>

    <span class="min-w-0 flex-1">
        <span class="block text-base font-semibold tracking-tight text-slate-900">{{ $label }}</span>
        @if (filled($description))
            <span class="mt-1 block text-sm leading-6 text-slate-500">{{ $description }}</span>
        @endif
        <span class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-indigo-600">
            Abrir
            <svg class="h-4 w-4 transition group-hover:translate-x-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
        </span>
    </span>
</a>
