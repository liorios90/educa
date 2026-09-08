<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Educa es el ecosistema educativo inteligente que se adapta a tu institución. Conecta a directivos, docentes, estudiantes y familias en un solo lugar.">

        <title>Educa — El ecosistema educativo inteligente</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-landing antialiased bg-slate-50 text-slate-900">
        <header
            x-data="{ open: false }"
            class="sticky top-0 z-50 border-b border-slate-200/70 bg-white/80 backdrop-blur-xl"
        >
            <div class="mx-auto flex h-[4.25rem] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-600/25">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 6l7.5 4.5M6 12.2v4.3L12 20l6-3.5v-4.3L12 15.4 6 12.2z" />
                        </svg>
                    </span>
                    <span class="text-[1.05rem] font-extrabold tracking-tight text-slate-900">Educa</span>
                </a>

                <nav class="hidden items-center gap-1 text-sm font-medium text-slate-600 md:flex">
                    <a href="#comunidad" class="rounded-full px-3.5 py-2 transition hover:bg-slate-100 hover:text-slate-900">Comunidad</a>
                    <a href="#flexibilidad" class="rounded-full px-3.5 py-2 transition hover:bg-slate-100 hover:text-slate-900">Flexibilidad</a>
                </nav>

                <div class="hidden items-center gap-3 md:flex">
                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                        >
                            Ir al panel
                        </a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="px-2 text-sm font-semibold text-slate-600 transition hover:text-slate-900">
                                Iniciar sesión
                            </a>
                        @endif

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                            >
                                Registrarse
                            </a>
                        @endif
                    @endauth
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl p-2 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 md:hidden"
                    @click="open = ! open"
                    :aria-expanded="open"
                    aria-controls="mobile-menu"
                    aria-label="Abrir menú"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div
                id="mobile-menu"
                x-show="open"
                x-cloak
                x-transition
                class="border-t border-slate-100 bg-white px-4 py-4 md:hidden"
            >
                <div class="flex flex-col gap-2 text-sm font-semibold text-slate-700">
                    <a href="#comunidad" class="rounded-xl px-3 py-2.5 hover:bg-slate-50" @click="open = false">Comunidad</a>
                    <a href="#flexibilidad" class="rounded-xl px-3 py-2.5 hover:bg-slate-50" @click="open = false">Flexibilidad</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-full bg-slate-900 px-3 py-2.5 text-center text-white">Ir al panel</a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="rounded-xl px-3 py-2.5 hover:bg-slate-50">Iniciar sesión</a>
                        @endif
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-full bg-slate-900 px-3 py-2.5 text-center text-white">Registrarse</a>
                        @endif
                    @endauth
                </div>
            </div>
        </header>

        <main>
            <section class="relative overflow-hidden landing-mesh">
                <div class="pointer-events-none absolute inset-0 landing-grid opacity-70"></div>
                <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-2 lg:gap-16 lg:px-8 lg:py-24">
                    <div class="flex flex-col items-start gap-7">
                        <p class="inline-flex items-center gap-2 rounded-full border border-indigo-200/80 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-indigo-700 shadow-sm">
                            <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                            Plataforma Educa
                        </p>
                        <h1 class="max-w-xl text-4xl font-extrabold tracking-tight text-slate-950 sm:text-5xl lg:text-[3.35rem] lg:leading-[1.08]">
                            El ecosistema educativo inteligente que se adapta a tu institución
                        </h1>
                        <p class="max-w-xl text-base leading-7 text-slate-600 sm:text-lg">
                            Transforma la gestión de tu centro educativo con una solución integral, flexible y diseñada para conectar a directivos, docentes, estudiantes y familias en un solo lugar.
                        </p>
                        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                            @guest
                                @if (Route::has('register'))
                                    <a
                                        href="{{ route('register') }}"
                                        class="inline-flex w-full items-center justify-center rounded-full bg-slate-950 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-950/15 transition hover:bg-slate-800 sm:w-auto"
                                    >
                                        Comenzar ahora
                                    </a>
                                @endif
                            @else
                                <a
                                    href="{{ route('dashboard') }}"
                                    class="inline-flex w-full items-center justify-center rounded-full bg-slate-950 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-950/15 transition hover:bg-slate-800 sm:w-auto"
                                >
                                    Ir al panel
                                </a>
                            @endguest
                            <a
                                href="#flexibilidad"
                                class="inline-flex w-full items-center justify-center rounded-full border border-slate-300 bg-white/90 px-6 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-400 hover:bg-white sm:w-auto"
                            >
                                Ver cómo se adapta
                            </a>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs font-medium text-slate-500">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-teal-500"></span>
                                Presencial, híbrido y virtual
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                                Todas las jornadas
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-violet-500"></span>
                                Ciclos a tu medida
                            </span>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="absolute -left-6 -top-8 h-28 w-28 rounded-full bg-indigo-400/20 blur-3xl"></div>
                        <div class="absolute -bottom-10 -right-4 h-32 w-32 rounded-full bg-teal-400/20 blur-3xl"></div>
                        <div class="relative rounded-[1.6rem] border border-white/70 bg-white/80 p-2 shadow-2xl shadow-slate-900/10 ring-1 ring-slate-200/80 backdrop-blur">
                            <div class="overflow-hidden rounded-[1.25rem] border border-slate-200 bg-slate-950">
                                <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                                        <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                                    </div>
                                    <p class="text-[11px] font-medium tracking-wide text-slate-400">educa.app / institución</p>
                                </div>
                                <div class="grid gap-0 lg:grid-cols-[9.5rem_1fr]">
                                    <aside class="hidden flex-col gap-1 border-r border-white/10 bg-slate-900/70 p-3 lg:flex">
                                        <p class="px-2 pb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Roles</p>
                                        <span class="rounded-lg bg-white/10 px-2.5 py-2 text-xs font-medium text-white">Directivos</span>
                                        <span class="rounded-lg px-2.5 py-2 text-xs text-slate-400">Docentes</span>
                                        <span class="rounded-lg px-2.5 py-2 text-xs text-slate-400">Estudiantes</span>
                                        <span class="rounded-lg px-2.5 py-2 text-xs text-slate-400">Familias</span>
                                    </aside>
                                    <div class="flex flex-col gap-4 bg-gradient-to-br from-slate-900 to-slate-800 p-4 sm:p-5">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex flex-col gap-1">
                                                <p class="text-[11px] font-medium uppercase tracking-wider text-indigo-300">Año escolar activo</p>
                                                <p class="text-sm font-semibold text-white">Colegio San Martín · 2026</p>
                                            </div>
                                            <span class="rounded-full bg-emerald-400/15 px-2.5 py-1 text-[11px] font-semibold text-emerald-300">Operativo</span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="rounded-xl bg-white/5 p-3 ring-1 ring-white/10">
                                                <p class="text-[10px] text-slate-400">Modalidad</p>
                                                <p class="mt-1 text-sm font-semibold text-white">Híbrida</p>
                                            </div>
                                            <div class="rounded-xl bg-white/5 p-3 ring-1 ring-white/10">
                                                <p class="text-[10px] text-slate-400">Jornada</p>
                                                <p class="mt-1 text-sm font-semibold text-white">Vespertina</p>
                                            </div>
                                            <div class="rounded-xl bg-white/5 p-3 ring-1 ring-white/10">
                                                <p class="text-[10px] text-slate-400">Régimen</p>
                                                <p class="mt-1 text-sm font-semibold text-white">Quimestral</p>
                                            </div>
                                        </div>
                                        <div class="flex flex-col gap-2.5 rounded-xl bg-white/5 p-3.5 ring-1 ring-white/10">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="text-slate-300">Conectados en un solo lugar</span>
                                                <span class="font-semibold text-white">4 roles</span>
                                            </div>
                                            <div class="h-1.5 overflow-hidden rounded-full bg-white/10">
                                                <div class="h-full w-4/5 rounded-full bg-gradient-to-r from-indigo-400 to-teal-400"></div>
                                            </div>
                                            <div class="flex flex-wrap gap-1.5 pt-1">
                                                <span class="rounded-full bg-indigo-400/15 px-2 py-0.5 text-[10px] font-medium text-indigo-200">Directivos</span>
                                                <span class="rounded-full bg-teal-400/15 px-2 py-0.5 text-[10px] font-medium text-teal-200">Docentes</span>
                                                <span class="rounded-full bg-amber-400/15 px-2 py-0.5 text-[10px] font-medium text-amber-200">Estudiantes</span>
                                                <span class="rounded-full bg-rose-400/15 px-2 py-0.5 text-[10px] font-medium text-rose-200">Familias</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-y border-slate-200 bg-white">
                <div class="mx-auto grid max-w-7xl grid-cols-1 divide-y divide-slate-200 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                    <div class="flex flex-col gap-1 px-6 py-7 sm:px-8">
                        <p class="text-2xl font-extrabold tracking-tight text-slate-950">3</p>
                        <p class="text-sm font-medium text-slate-500">Modalidades académicas cubiertas</p>
                    </div>
                    <div class="flex flex-col gap-1 px-6 py-7 sm:px-8">
                        <p class="text-2xl font-extrabold tracking-tight text-slate-950">4+</p>
                        <p class="text-sm font-medium text-slate-500">Jornadas independientes o unificadas</p>
                    </div>
                    <div class="flex flex-col gap-1 px-6 py-7 sm:px-8">
                        <p class="text-2xl font-extrabold tracking-tight text-slate-950">∞</p>
                        <p class="text-sm font-medium text-slate-500">Ciclos y calendarios personalizados</p>
                    </div>
                </div>
            </section>

            <section id="comunidad" class="bg-slate-50 py-20 sm:py-24">
                <div class="mx-auto flex max-w-7xl flex-col gap-12 px-4 sm:px-6 lg:px-8">
                    <div class="flex max-w-2xl flex-col gap-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">Comunidad</p>
                        <h2 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Una comunidad educativa conectada</h2>
                        <p class="text-base leading-7 text-slate-600">
                            Educa reúne a todos los actores de tu institución para que la información circule con claridad y cada rol tenga lo que necesita.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <article class="group flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-lg hover:shadow-indigo-950/5">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21V8.25L12 3l8.25 5.25V21M9 21v-6h6v6" />
                                </svg>
                            </span>
                            <h3 class="text-lg font-bold text-slate-950">Directivos</h3>
                            <p class="text-sm leading-6 text-slate-600">Visión institucional, control académico y decisiones con información unificada.</p>
                        </article>
                        <article class="group flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-lg hover:shadow-teal-950/5">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-700 ring-1 ring-teal-100">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75v12.75m0-12.75C10.832 5.977 9.246 5.5 7.5 5.5S4.168 5.977 3 6.75v12.75C4.168 18.977 5.754 18.5 7.5 18.5s3.332.477 4.5 1.25m0-12.75C13.168 5.977 14.754 5.5 16.5 5.5c1.746 0 3.332.477 4.5 1.25v12.75C19.832 18.977 18.246 18.5 16.5 18.5c-1.746 0-3.332.477-4.5 1.25" />
                                </svg>
                            </span>
                            <h3 class="text-lg font-bold text-slate-950">Docentes</h3>
                            <p class="text-sm leading-6 text-slate-600">Herramientas para planificar, evaluar y acompañar el aprendizaje de cada grupo.</p>
                        </article>
                        <article class="group flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-lg hover:shadow-amber-950/5">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 ring-1 ring-amber-100">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.44 60.44 0 00-.491 6.347A48.62 48.62 0 0112 16.5c2.773 0 5.491-.21 8.231-.605a60.44 60.44 0 00-.491-6.347M12 4.5c2.21 0 4.21.894 5.657 2.343M12 4.5A7.99 7.99 0 006.343 6.843M12 4.5v12" />
                                </svg>
                            </span>
                            <h3 class="text-lg font-bold text-slate-950">Estudiantes</h3>
                            <p class="text-sm leading-6 text-slate-600">Acceso claro a su progreso, actividades y comunicación con su centro.</p>
                        </article>
                        <article class="group flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-rose-200 hover:shadow-lg hover:shadow-rose-950/5">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-700 ring-1 ring-rose-100">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0" />
                                </svg>
                            </span>
                            <h3 class="text-lg font-bold text-slate-950">Familias</h3>
                            <p class="text-sm leading-6 text-slate-600">Acompañamiento cercano al proceso educativo, con información oportuna y transparente.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="flexibilidad" class="bg-white py-20 sm:py-24">
                <div class="mx-auto grid max-w-7xl items-start gap-12 px-4 sm:px-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:gap-16 lg:px-8">
                    <div class="flex flex-col gap-5 lg:sticky lg:top-28">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">Flexibilidad</p>
                        <h2 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Una plataforma, infinitas posibilidades</h2>
                        <p class="text-base leading-7 text-slate-600 sm:text-lg">
                            Sabemos que cada institución es única. Por eso, nuestro sistema está diseñado para amoldarse por completo a tus necesidades estructurales, sin importar cómo organices tu año escolar:
                        </p>
                    </div>

                    <div class="flex flex-col gap-4">
                        <article class="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-slate-50 p-7 sm:flex-row sm:items-start sm:gap-6">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/20">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12h19.5M12 2.25c3 3.6 3 15.9 0 19.5M12 2.25c-3 3.6-3 15.9 0 19.5" />
                                </svg>
                            </span>
                            <div class="flex flex-col gap-2">
                                <h3 class="text-xl font-bold text-slate-950">Cualquier modalidad</h3>
                                <p class="text-sm leading-6 text-slate-600">
                                    Totalmente funcional para entornos presenciales, semipresenciales (híbridos) y educación 100% virtual o a distancia.
                                </p>
                                <div class="flex flex-wrap gap-2 pt-1">
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200">Presencial</span>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200">Híbrido</span>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200">Virtual</span>
                                </div>
                            </div>
                        </article>
                        <article class="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-7 shadow-sm sm:flex-row sm:items-start sm:gap-6">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-teal-600 text-white shadow-lg shadow-teal-600/20">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l3.75 2.25M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div class="flex flex-col gap-2">
                                <h3 class="text-xl font-bold text-slate-950">Todas las jornadas</h3>
                                <p class="text-sm leading-6 text-slate-600">
                                    Gestión independiente o unificada para jornadas matutinas, vespertinas, nocturnas y fines de semana.
                                </p>
                                <div class="flex flex-wrap gap-2 pt-1">
                                    <span class="rounded-full bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200">Matutina</span>
                                    <span class="rounded-full bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200">Vespertina</span>
                                    <span class="rounded-full bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200">Nocturna</span>
                                    <span class="rounded-full bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200">Fines de semana</span>
                                </div>
                            </div>
                        </article>
                        <article class="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-slate-950 p-7 text-white sm:flex-row sm:items-start sm:gap-6">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-amber-300 ring-1 ring-white/10">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M5.25 6.75h13.5A1.5 1.5 0 0120.25 8.25v10.5a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5V8.25a1.5 1.5 0 011.5-1.5z" />
                                </svg>
                            </span>
                            <div class="flex flex-col gap-2">
                                <h3 class="text-xl font-bold">Flexibilidad temporal</h3>
                                <p class="text-sm leading-6 text-slate-300">
                                    Configuración inmediata para regímenes semestrales, quimestrales, trimestrales, bimestrales o ciclos personalizados.
                                </p>
                                <div class="flex flex-wrap gap-2 pt-1">
                                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">Semestral</span>
                                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">Quimestral</span>
                                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">Trimestral</span>
                                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">Bimestral</span>
                                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 ring-1 ring-white/10">Personalizado</span>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="bg-slate-50 pb-20 sm:pb-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="relative overflow-hidden rounded-[2rem] bg-slate-950 px-6 py-14 text-white sm:px-12 lg:px-16">
                        <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-indigo-500/30 blur-3xl"></div>
                        <div class="pointer-events-none absolute -bottom-24 left-20 h-56 w-56 rounded-full bg-teal-400/20 blur-3xl"></div>
                        <div class="relative flex flex-col items-start gap-6 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex max-w-2xl flex-col gap-3">
                                <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Una plataforma para toda tu institución</h2>
                                <p class="text-sm leading-7 text-slate-300 sm:text-base">
                                    Da el siguiente paso y gestiona tu centro educativo con la flexibilidad que Educa pone al servicio de tu modelo académico.
                                </p>
                            </div>
                            @guest
                                @if (Route::has('register'))
                                    <a
                                        href="{{ route('register') }}"
                                        class="inline-flex shrink-0 items-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-950 shadow-sm transition hover:bg-slate-100"
                                    >
                                        Crear una cuenta
                                    </a>
                                @endif
                            @else
                                <a
                                    href="{{ route('dashboard') }}"
                                    class="inline-flex shrink-0 items-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-950 shadow-sm transition hover:bg-slate-100"
                                >
                                    Continuar al panel
                                </a>
                            @endguest
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-slate-200 bg-white py-10">
            <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-950 text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 6l7.5 4.5M6 12.2v4.3L12 20l6-3.5v-4.3L12 15.4 6 12.2z" />
                        </svg>
                    </span>
                    <div class="flex flex-col">
                        <p class="text-sm font-bold text-slate-950">Educa</p>
                        <p class="text-xs text-slate-500">El ecosistema educativo inteligente.</p>
                    </div>
                </div>
                <div class="flex items-center gap-6 text-sm font-medium text-slate-500">
                    <a href="#comunidad" class="hover:text-slate-900">Comunidad</a>
                    <a href="#flexibilidad" class="hover:text-slate-900">Flexibilidad</a>
                </div>
                <p class="text-xs text-slate-400">&copy; {{ date('Y') }} Educa. Todos los derechos reservados.</p>
            </div>
        </footer>
    </body>
</html>
