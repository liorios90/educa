@props(['name' => 'dot'])

@php
    $classes = 'h-5 w-5 shrink-0';
@endphp

<svg {{ $attributes->merge(['class' => $classes]) }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
    @foreach (\App\Navigation\NavigationIcons::paths($name) as $path)
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
    @endforeach
</svg>
