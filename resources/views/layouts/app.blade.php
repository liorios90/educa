<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div
            class="min-h-screen bg-slate-50"
            x-data="{
                sidebarOpen: false,
                init() {
                    const stored = localStorage.getItem('educa.sidebarOpen');

                    if (stored === 'true' || stored === 'false') {
                        this.sidebarOpen = stored === 'true';
                    } else {
                        this.sidebarOpen = window.matchMedia('(min-width: 1024px)').matches;
                    }

                    this.$watch('sidebarOpen', (value) => {
                        localStorage.setItem('educa.sidebarOpen', String(value));
                    });
                },
            }"
            @keydown.escape.window="sidebarOpen = false"
        >
            <x-sidebar />

            <div
                class="transition-[padding] duration-200 ease-out lg:pl-72"
                :style="sidebarOpen ? {} : { paddingLeft: '0px' }"
            >
                <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/80 backdrop-blur">
                    <div class="flex h-16 items-center gap-4 px-4 sm:px-6 lg:px-8">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                            x-cloak
                            x-show="! sidebarOpen"
                            @click="sidebarOpen = true"
                            aria-controls="app-sidebar"
                            :aria-expanded="sidebarOpen.toString()"
                        >
                            <span class="sr-only">Abrir menú</span>
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>

                        <div class="min-w-0 flex-1">
                            @isset($header)
                                {{ $header }}
                            @endisset
                        </div>

                        <x-user-toolbar />
                    </div>
                </header>

                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
