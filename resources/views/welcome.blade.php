<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Educa conecta la gestión académica, docentes, estudiantes y familias en una plataforma educativa flexible y fácil de usar.">

        <title>Educa — Una escuela conectada</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            @keyframes puzzle-assemble {
                0% { opacity: 0; transform: translate(var(--from-x), var(--from-y)) rotate(var(--from-rotation)) scale(.72); }
                72% { opacity: 1; transform: translate(0, 0) rotate(0) scale(1.08); }
                100% { opacity: 1; transform: translate(0, 0) rotate(0) scale(1); }
            }

            @keyframes intro-disappear {
                0%, 72% { opacity: 1; visibility: visible; }
                100% { opacity: 0; visibility: hidden; }
            }

            .intro-splash {
                animation: intro-disappear 1.15s ease 1.3s forwards;
            }

            .puzzle-piece {
                opacity: 0;
                animation: puzzle-assemble .72s cubic-bezier(.2, .8, .2, 1) forwards;
            }

            .puzzle-piece-one { --from-x: -24px; --from-y: -24px; --from-rotation: -18deg; animation-delay: .1s; }
            .puzzle-piece-two { --from-x: 24px; --from-y: -24px; --from-rotation: 18deg; animation-delay: .24s; }
            .puzzle-piece-three { --from-x: -24px; --from-y: 24px; --from-rotation: 18deg; animation-delay: .38s; }
            .puzzle-piece-four { --from-x: 24px; --from-y: 24px; --from-rotation: -18deg; animation-delay: .52s; }

            @media (prefers-reduced-motion: reduce) {
                .intro-splash { animation-duration: .01ms; animation-delay: .6s; }
                .puzzle-piece { opacity: 1; animation: none; transform: none; }
            }
        </style>
    </head>
    <body class="bg-[#f8faf8] font-landing antialiased text-slate-900 selection:bg-emerald-200 selection:text-emerald-950">
        <div class="intro-splash fixed inset-0 z-[100] flex items-center justify-center bg-[#f8faf8]" aria-hidden="true">
            <div class="flex flex-col items-center gap-4">
                <svg class="h-24 w-24" viewBox="0 0 100 100" fill="none" aria-hidden="true">
                    <g class="puzzle-piece puzzle-piece-one">
                        <rect x="17" y="17" width="31" height="31" rx="10" fill="#047857" />
                        <circle cx="48" cy="32.5" r="6" fill="#047857" />
                    </g>
                    <g class="puzzle-piece puzzle-piece-two">
                        <rect x="52" y="17" width="31" height="31" rx="10" fill="#0f766e" />
                        <circle cx="52" cy="32.5" r="5" fill="#f8faf8" />
                    </g>
                    <g class="puzzle-piece puzzle-piece-three">
                        <rect x="17" y="52" width="31" height="31" rx="10" fill="#d97706" />
                        <circle cx="32.5" cy="52" r="5" fill="#f8faf8" />
                    </g>
                    <g class="puzzle-piece puzzle-piece-four">
                        <rect x="52" y="52" width="31" height="31" rx="10" fill="#4f46e5" />
                        <circle cx="67.5" cy="52" r="6" fill="#4f46e5" />
                    </g>
                </svg>
                <span class="text-sm font-bold tracking-[0.24em] text-emerald-950">EDUCA</span>
            </div>
        </div>

        <header x-data="{ open: false }" class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl">
            <div class="mx-auto flex h-[4.5rem] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5" aria-label="Educa, página de inicio">
                    <span class="grid h-9 w-9 grid-cols-2 gap-0.5 rounded-xl bg-emerald-900 p-1.5" aria-hidden="true">
                        <span class="rounded-tl-md rounded-br-md bg-emerald-100"></span>
                        <span class="rounded-tr-md rounded-bl-md bg-teal-300"></span>
                        <span class="rounded-bl-md rounded-tr-md bg-amber-300"></span>
                        <span class="rounded-br-md rounded-tl-md bg-white"></span>
                    </span>
                    <span class="text-lg font-extrabold tracking-tight text-slate-950">Educa</span>
                </a>

                <nav class="hidden items-center gap-1 text-sm font-medium text-slate-600 md:flex" aria-label="Navegación principal">
                    <a href="#plataforma" class="rounded-full px-3.5 py-2 transition hover:bg-slate-100 hover:text-emerald-900">La plataforma</a>
                    <a href="#comunidad" class="rounded-full px-3.5 py-2 transition hover:bg-slate-100 hover:text-emerald-900">Comunidad</a>
                    <a href="#flexibilidad" class="rounded-full px-3.5 py-2 transition hover:bg-slate-100 hover:text-emerald-900">Flexibilidad</a>
                </nav>

                <div class="hidden items-center gap-3 md:flex">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-full bg-emerald-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800">Ir al panel</a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="px-2 text-sm font-semibold text-slate-600 transition hover:text-emerald-900">Iniciar sesión</a>
                        @endif
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-emerald-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800">Crear cuenta</a>
                        @endif
                    @endauth
                </div>

                <button type="button" class="inline-flex items-center justify-center rounded-xl p-2 text-slate-600 transition hover:bg-slate-100 md:hidden" @click="open = ! open" :aria-expanded="open" aria-controls="mobile-menu" aria-label="Abrir menú">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div id="mobile-menu" x-show="open" x-cloak x-transition class="border-t border-slate-100 bg-white px-4 py-4 md:hidden">
                <div class="flex flex-col gap-2 text-sm font-semibold text-slate-700">
                    <a href="#plataforma" class="rounded-xl px-3 py-2.5 hover:bg-slate-50" @click="open = false">La plataforma</a>
                    <a href="#comunidad" class="rounded-xl px-3 py-2.5 hover:bg-slate-50" @click="open = false">Comunidad</a>
                    <a href="#flexibilidad" class="rounded-xl px-3 py-2.5 hover:bg-slate-50" @click="open = false">Flexibilidad</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-full bg-emerald-900 px-3 py-2.5 text-center text-white">Ir al panel</a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="rounded-xl px-3 py-2.5 hover:bg-slate-50">Iniciar sesión</a>
                        @endif
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-full bg-emerald-900 px-3 py-2.5 text-center text-white">Crear cuenta</a>
                        @endif
                    @endauth
                </div>
            </div>
        </header>

        <main>
            <section id="plataforma" class="overflow-hidden bg-white">
                <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-2 lg:gap-16 lg:px-8 lg:py-24">
                    <div class="flex flex-col items-start gap-6">
                        <p class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3.5 py-1.5 text-xs font-bold uppercase tracking-[0.16em] text-emerald-900">
                            <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                            Tecnología al servicio de la educación
                        </p>
                        <h1 class="max-w-xl text-4xl font-extrabold leading-[1.08] tracking-tight text-slate-950 sm:text-5xl lg:text-[3.7rem]">
                            La vida escolar, <span class="text-emerald-800">más conectada.</span>
                        </h1>
                        <p class="max-w-xl text-base leading-8 text-slate-600 sm:text-lg">
                            Organiza la gestión académica y acerca a directivos, docentes, estudiantes y familias en una plataforma pensada para cada institución.
                        </p>
                        <div class="flex w-full flex-col gap-3 pt-1 sm:w-auto sm:flex-row">
                            @guest
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full bg-emerald-900 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-950/15 transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                                        Empieza con Educa
                                        <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" /></svg>
                                    </a>
                                @endif
                            @else
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-full bg-emerald-900 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-950/15 transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Ir al panel</a>
                            @endguest
                            <a href="#comunidad" class="inline-flex items-center justify-center rounded-full border border-slate-300 px-6 py-3.5 text-sm font-semibold text-slate-800 transition hover:border-emerald-700 hover:text-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Descubrir la plataforma</a>
                        </div>
                        <p class="text-sm font-medium text-slate-500">Flexible para colegios e institutos.</p>
                    </div>

                    <div class="relative">
                        <div class="absolute -right-12 -top-12 h-48 w-48 rounded-full bg-emerald-100 blur-3xl"></div>
                        <div class="absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-amber-100/80 blur-3xl"></div>
                        <div class="relative rounded-[2rem] border border-slate-200 bg-[#f8faf8] p-4 shadow-xl shadow-slate-900/5 sm:p-6">
                            <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-800">Un espacio para aprender</p>
                                    <p class="mt-1 text-lg font-bold text-slate-950">Todo en un mismo lugar</p>
                                </div>
                                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-900 text-white" aria-hidden="true">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 6l7.5 4.5M6 12.2v4.3L12 20l6-3.5v-4.3L12 15.4 6 12.2z" /></svg>
                                </span>
                            </div>
                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <div class="rounded-2xl border border-emerald-100 bg-white p-4">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800" aria-hidden="true"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21V8.25L12 3l8.25 5.25V21M9 21v-6h6v6" /></svg></span>
                                    <p class="mt-3 text-sm font-bold text-slate-900">Gestión académica</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">Información clara para organizar el día escolar.</p>
                                </div>
                                <div class="rounded-2xl border border-amber-100 bg-white p-4">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-800" aria-hidden="true"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75v12.75m0-12.75C10.832 5.977 9.246 5.5 7.5 5.5S4.168 5.977 3 6.75v12.75C4.168 18.977 5.754 18.5 7.5 18.5s3.332.477 4.5 1.25m0-12.75C13.168 5.977 14.754 5.5 16.5 5.5c1.746 0 3.332.477 4.5 1.25v12.75C19.832 18.977 18.246 18.5 16.5 18.5c-1.746 0-3.332.477-4.5 1.25" /></svg></span>
                                    <p class="mt-3 text-sm font-bold text-slate-900">Aula y aprendizaje</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">Herramientas para acompañar cada proceso.</p>
                                </div>
                                <div class="rounded-2xl border border-sky-100 bg-white p-4">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-800" aria-hidden="true"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0" /></svg></span>
                                    <p class="mt-3 text-sm font-bold text-slate-900">Comunicación</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">Familias y equipos, más cerca.</p>
                                </div>
                                <div class="rounded-2xl border border-violet-100 bg-white p-4">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-800" aria-hidden="true"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M5.25 6.75h13.5A1.5 1.5 0 0120.25 8.25v10.5a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5V8.25a1.5 1.5 0 011.5-1.5z" /></svg></span>
                                    <p class="mt-3 text-sm font-bold text-slate-900">Calendario escolar</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">Periodos ajustados a tu institución.</p>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap items-center gap-2 rounded-2xl bg-emerald-950 px-4 py-3 text-xs font-medium text-emerald-50">
                                <span class="font-bold text-white">Una comunidad:</span>
                                <span>directivos</span><span class="text-emerald-400">·</span><span>docentes</span><span class="text-emerald-400">·</span><span>estudiantes</span><span class="text-emerald-400">·</span><span>familias</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="comunidad" class="bg-[#f8faf8] py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto flex max-w-2xl flex-col items-center gap-4 text-center">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-800">Para toda la comunidad</p>
                        <h2 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Cada rol tiene su lugar.</h2>
                        <p class="text-base leading-7 text-slate-600">Educa pone la información y las herramientas adecuadas al alcance de quienes participan en la vida escolar.</p>
                    </div>
                    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:-translate-y-1 hover:shadow-lg">
                            <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">01</p>
                            <h3 class="mt-3 text-lg font-bold text-slate-950">Directivos</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Organización y seguimiento de la gestión institucional.</p>
                        </article>
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:-translate-y-1 hover:shadow-lg">
                            <p class="text-xs font-bold uppercase tracking-wider text-teal-800">02</p>
                            <h3 class="mt-3 text-lg font-bold text-slate-950">Docentes</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Planificación y acompañamiento del aprendizaje.</p>
                        </article>
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:-translate-y-1 hover:shadow-lg">
                            <p class="text-xs font-bold uppercase tracking-wider text-amber-800">03</p>
                            <h3 class="mt-3 text-lg font-bold text-slate-950">Estudiantes</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Actividades y progreso escolar a su alcance.</p>
                        </article>
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:-translate-y-1 hover:shadow-lg">
                            <p class="text-xs font-bold uppercase tracking-wider text-violet-800">04</p>
                            <h3 class="mt-3 text-lg font-bold text-slate-950">Familias</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Comunicación cercana con la institución.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="flexibilidad" class="bg-white py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex max-w-2xl flex-col gap-4">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-800">Se adapta a ti</p>
                        <h2 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Una plataforma, distintas formas de aprender.</h2>
                        <p class="text-base leading-7 text-slate-600">Configura Educa de acuerdo con la organización y el calendario de tu colegio o instituto.</p>
                    </div>
                    <div class="mt-9 grid gap-4 md:grid-cols-3">
                        <article class="rounded-3xl bg-emerald-50 p-6 sm:p-7">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-800 text-white" aria-hidden="true"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zM2.25 12h19.5M12 2.25c3 3.6 3 15.9 0 19.5" /></svg></span>
                            <h3 class="mt-5 text-lg font-bold text-slate-950">Modalidades</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Presencial, híbrida y virtual.</p>
                        </article>
                        <article class="rounded-3xl bg-amber-50 p-6 sm:p-7">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-700 text-white" aria-hidden="true"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l3.75 2.25M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></span>
                            <h3 class="mt-5 text-lg font-bold text-slate-950">Jornadas</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Mañana, tarde, noche y fines de semana.</p>
                        </article>
                        <article class="rounded-3xl bg-violet-50 p-6 sm:p-7">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-700 text-white" aria-hidden="true"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M5.25 6.75h13.5A1.5 1.5 0 0120.25 8.25v10.5a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5V8.25a1.5 1.5 0 011.5-1.5z" /></svg></span>
                            <h3 class="mt-5 text-lg font-bold text-slate-950">Periodos</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Semestrales, quimestrales, trimestrales o personalizados.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="bg-emerald-950 px-4 py-14 text-white sm:px-6 sm:py-16 lg:px-8">
                <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
                    <div class="flex max-w-2xl flex-col gap-3">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-300">Educa</p>
                        <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Conecta tu comunidad educativa.</h2>
                        <p class="text-sm leading-7 text-emerald-100 sm:text-base">Un espacio flexible para organizar y acompañar la vida escolar.</p>
                    </div>
                    @guest
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex shrink-0 items-center justify-center rounded-full bg-white px-6 py-3.5 text-sm font-bold text-emerald-950 transition hover:bg-emerald-50">Crear una cuenta</a>
                        @endif
                    @else
                        <a href="{{ route('dashboard') }}" class="inline-flex shrink-0 items-center justify-center rounded-full bg-white px-6 py-3.5 text-sm font-bold text-emerald-950 transition hover:bg-emerald-50">Continuar al panel</a>
                    @endguest
                </div>
            </section>
        </main>

        <footer class="border-t border-slate-200 bg-white py-8">
            <div class="mx-auto flex max-w-7xl flex-col gap-5 px-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="grid h-8 w-8 grid-cols-2 gap-0.5 rounded-lg bg-emerald-900 p-1.5" aria-hidden="true"><span class="rounded-tl-md rounded-br-md bg-emerald-100"></span><span class="rounded-tr-md rounded-bl-md bg-teal-300"></span><span class="rounded-bl-md rounded-tr-md bg-amber-300"></span><span class="rounded-br-md rounded-tl-md bg-white"></span></span>
                    <span class="text-sm font-bold text-slate-950">Educa</span>
                </a>
                <nav class="flex flex-wrap items-center gap-5 text-sm font-medium text-slate-500" aria-label="Navegación del pie de página">
                    <a href="#plataforma" class="hover:text-emerald-900">Plataforma</a>
                    <a href="#comunidad" class="hover:text-emerald-900">Comunidad</a>
                    <a href="#flexibilidad" class="hover:text-emerald-900">Flexibilidad</a>
                    <a href="{{ route('privacy') }}" class="hover:text-emerald-900">Política de privacidad</a>
                    <a href="{{ route('terms') }}" class="hover:text-emerald-900">Términos y condiciones</a>
                    <a href="{{ route('cookies') }}" class="hover:text-emerald-900">Política de cookies</a>
                </nav>
                <p class="text-xs text-slate-400">&copy; {{ date('Y') }} Educa. Todos los derechos reservados.</p>
            </div>
        </footer>
    </body>
</html>
