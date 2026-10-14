<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Política de cookies de Educa: qué cookies usamos, para qué sirven y por qué no pedimos un consentimiento previo.">

        <title>Política de cookies — Educa</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f8faf8] font-landing antialiased text-slate-900">
        <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl">
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

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-full bg-emerald-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800">Ir al panel</a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="px-2 text-sm font-semibold text-slate-600 transition hover:text-emerald-900">Iniciar sesión</a>
                        @endif
                    @endauth
                </div>
            </div>
        </header>

        <main class="px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <article class="mx-auto flex max-w-3xl flex-col gap-8">
                <header class="flex flex-col gap-3">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-800">Información legal</p>
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Política de cookies</h1>
                    <p class="text-sm text-slate-500">Última actualización: 14 de octubre de 2026</p>
                </header>

                <p class="text-base leading-7 text-slate-600">
                    Esta política explica qué cookies y almacenamientos similares usa Educa. Se complementa con la <a href="{{ route('privacy') }}" class="font-semibold text-emerald-900 underline hover:text-emerald-800">política de privacidad</a> y los <a href="{{ route('terms') }}" class="font-semibold text-emerald-900 underline hover:text-emerald-800">términos y condiciones</a>.
                </p>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">1. ¿Hace falta tu consentimiento?</h2>
                    <p class="text-base leading-7 text-slate-600">
                        No. Educa solo utiliza cookies estrictamente necesarias para que el sitio funcione: iniciar sesión, proteger los formularios y mantener el contexto de tu institución (rol, modalidad, jornada y periodo). No usamos cookies de publicidad, analítica, redes sociales ni seguimiento.
                    </p>
                    <p class="text-base leading-7 text-slate-600">
                        Según la Ley Orgánica de Protección de Datos Personales del Ecuador, el tratamiento necesario para prestar el servicio que solicitas no requiere un consentimiento previo adicional. Por eso no mostramos un aviso para “aceptar cookies”: no puedes rechazarlas y seguir usando Educa, porque el servicio no funcionaría.
                    </p>
                    <p class="text-base leading-7 text-slate-600">
                        Si en el futuro incorporamos cookies no esenciales (por ejemplo, estadísticas o marketing), pediremos tu consentimiento antes de activarlas y actualizaríamos esta política.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">2. Cookies que usamos</h2>
                    <ul class="flex list-disc flex-col gap-2 pl-5 text-base leading-7 text-slate-600">
                        <li>
                            <span class="font-semibold text-slate-800">{{ config('session.cookie') }}</span>:
                            cookie de sesión. Identifica tu visita para mantenerte autenticado y recordar el contexto académico activo. Caduca a los {{ (int) config('session.lifetime') }} minutos de inactividad, salvo que el navegador se cierre antes.
                        </li>
                        <li>
                            <span class="font-semibold text-slate-800">XSRF-TOKEN</span>:
                            protege los formularios contra envíos no autorizados. Es técnica y necesaria para la seguridad del sitio.
                        </li>
                        <li>
                            <span class="font-semibold text-slate-800">remember_web_*</span>:
                            solo se crea si marcas “Recordarme” al iniciar sesión. Mantiene tu acceso en ese navegador hasta que cierres sesión o borres las cookies.
                        </li>
                    </ul>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">3. Otros almacenamientos</h2>
                    <p class="text-base leading-7 text-slate-600">
                        El panel guarda en el navegador (<span class="font-semibold text-slate-800">localStorage</span>) si la barra lateral está abierta o cerrada. No es una cookie, no se envía al servidor y solo sirve para recordar esa preferencia de interfaz.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">4. Servicios de terceros</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Las fuentes tipográficas se cargan desde fonts.bunny.net para mostrar el diseño del sitio. Educa no coloca cookies de publicidad a través de ese servicio. Si el proveedor registra la solicitud técnica (por ejemplo, la dirección IP), su propia política de privacidad aplica a ese tratamiento.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">5. Cómo gestionarlas</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Puedes borrar o bloquear cookies desde la configuración de tu navegador. Si lo haces, es posible que no puedas iniciar sesión, enviar formularios o conservar el contexto de tu institución. Para dejar de usar la cookie “Recordarme”, cierra sesión o desmarca esa opción la próxima vez que entres.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">6. Cambios</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Actualizaremos esta página si cambia el uso de cookies. La fecha de la última actualización aparece al inicio.
                    </p>
                </section>
            </article>
        </main>

        <footer class="border-t border-slate-200 bg-white py-8">
            <div class="mx-auto flex max-w-7xl flex-col gap-5 px-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="grid h-8 w-8 grid-cols-2 gap-0.5 rounded-lg bg-emerald-900 p-1.5" aria-hidden="true"><span class="rounded-tl-md rounded-br-md bg-emerald-100"></span><span class="rounded-tr-md rounded-bl-md bg-teal-300"></span><span class="rounded-bl-md rounded-tr-md bg-amber-300"></span><span class="rounded-br-md rounded-tl-md bg-white"></span></span>
                    <span class="text-sm font-bold text-slate-950">Educa</span>
                </a>
                <nav class="flex flex-wrap items-center gap-5 text-sm font-medium text-slate-500" aria-label="Navegación del pie de página">
                    <a href="{{ route('home') }}" class="hover:text-emerald-900">Inicio</a>
                    <a href="{{ route('privacy') }}" class="hover:text-emerald-900">Política de privacidad</a>
                    <a href="{{ route('terms') }}" class="hover:text-emerald-900">Términos y condiciones</a>
                    <a href="{{ route('cookies') }}" class="text-emerald-900" aria-current="page">Política de cookies</a>
                </nav>
                <p class="text-xs text-slate-400">&copy; {{ date('Y') }} Educa. Todos los derechos reservados.</p>
            </div>
        </footer>
    </body>
</html>
