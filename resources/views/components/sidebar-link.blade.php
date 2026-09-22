@props([
    'href',
    'active' => false,
    'icon' => 'dot',
    'nested' => false,
])

@php
    $classes = $active
        ? 'bg-white/10 text-white shadow-sm ring-1 ring-white/10'
        : 'text-slate-300 hover:bg-white/5 hover:text-white';
    $padding = $nested ? 'px-3 py-2' : 'px-3 py-2.5';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'group flex items-center gap-3 rounded-xl '.$padding.' text-sm font-medium transition '.$classes]) }}>
    <x-sidebar-icon :name="$icon" class="{{ $active ? 'text-indigo-300' : 'text-slate-400 group-hover:text-slate-200' }}" />
    <span>{{ $slot }}</span>
</a>
