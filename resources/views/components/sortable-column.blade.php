@props([
    'field',
    'current',
    'direction',
    'route',
])

@php
    $isActive = $current === $field;
    $nextDirection = $isActive && $direction === 'asc' ? 'desc' : 'asc';
    $ariaSort = $isActive
        ? ($direction === 'asc' ? 'ascending' : 'descending')
        : 'none';
    $query = array_merge(
        request()->except(['sort', 'direction', 'page']),
        [
            'sort' => $field,
            'direction' => $nextDirection,
        ],
    );
@endphp

<th {{ $attributes->merge(['class' => 'px-6 py-3 font-medium']) }} aria-sort="{{ $ariaSort }}">
    <a
        href="{{ route($route, $query) }}"
        class="inline-flex items-center gap-1 text-slate-600 hover:text-slate-900"
    >
        <span>{{ $slot }}</span>
        <svg class="h-3.5 w-3.5 {{ $isActive ? 'text-indigo-500' : 'text-slate-300' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            @if ($isActive && $direction === 'desc')
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            @else
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
            @endif
        </svg>
    </a>
</th>
