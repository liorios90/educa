<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Términos y condiciones de uso de Educa, la plataforma de gestión académica para instituciones educativas.">

        <title>Términos y condiciones — Educa</title>

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
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Términos y condiciones</h1>
                    <p class="text-sm text-slate-500">Última actualización: 14 de octubre de 2026</p>
                </header>

                <p class="text-base leading-7 text-slate-600">
                    Estos términos regulan el uso de Educa, la plataforma de gestión académica para instituciones educativas. Al crear una cuenta o usar el servicio, aceptas estas condiciones y la <a href="{{ route('privacy') }}" class="font-semibold text-emerald-900 underline hover:text-emerald-800">política de privacidad</a>.
                </p>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">1. El servicio</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Educa permite a cada establecimiento organizar su gestión académica y acercar a directivos, docentes, estudiantes y familias. El alcance disponible depende de la configuración de la institución, del rol del usuario y del periodo o jornada activos.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">2. Cuentas y acceso</h2>
                    <ul class="flex list-disc flex-col gap-2 pl-5 text-base leading-7 text-slate-600">
                        <li>Debes proporcionar información veraz y mantenerla actualizada.</li>
                        <li>Eres responsable de tu contraseña y de la actividad realizada con tu cuenta.</li>
                        <li>No debes compartir credenciales ni intentar acceder a datos de otra institución o de un rol que no te corresponda.</li>
                        <li>La institución puede crear, modificar o desactivar cuentas de su comunidad educativa.</li>
                    </ul>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">3. Responsabilidad de la institución</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Cada establecimiento es responsable de los datos que registra, de autorizar a sus usuarios y de cumplir la normativa educativa aplicable. Educa presta la plataforma tecnológica; no sustituye las decisiones académicas, administrativas ni legales de la institución.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">4. Uso aceptable</h2>
                    <p class="text-base leading-7 text-slate-600">Queda prohibido usar Educa para:</p>
                    <ul class="flex list-disc flex-col gap-2 pl-5 text-base leading-7 text-slate-600">
                        <li>Cargar información falsa, ilícita o que vulnere derechos de terceros.</li>
                        <li>Interferir con la seguridad, la disponibilidad o el funcionamiento del servicio.</li>
                        <li>Extraer, copiar o divulgar datos de estudiantes, familias o personal sin autorización.</li>
                        <li>Usar la plataforma con fines ajenos a la gestión educativa de la institución.</li>
                    </ul>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">5. Contenido y propiedad</h2>
                    <p class="text-base leading-7 text-slate-600">
                        La institución conserva los derechos sobre la información académica y administrativa que registra. Educa y sus marcas, diseño e interfaces pertenecen a quien opera la plataforma. No puedes copiar, modificar ni explotar el software más allá del uso autorizado del servicio.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">6. Disponibilidad</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Procuramos mantener Educa disponible y segura, pero el servicio se ofrece “tal como está”. Pueden existir mantenimientos, interrupciones o errores. No garantizamos que el servicio sea ininterrumpido ni que cubra todas las necesidades particulares de cada institución.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">7. Limitación de responsabilidad</h2>
                    <p class="text-base leading-7 text-slate-600">
                        En la medida que permita la ley ecuatoriana, Educa no responde por daños indirectos, pérdida de datos causada por un uso indebido, ni por decisiones que la institución tome con la información registrada en la plataforma. La responsabilidad de cada parte se limita al uso del servicio conforme a estos términos.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">8. Suspensión y cierre</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Podemos suspender o cancelar el acceso si se incumplen estos términos, si hay un riesgo de seguridad o si la institución deja de usar el servicio. Tú o la institución pueden solicitar el cierre de cuentas a través del administrador del establecimiento.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">9. Cambios</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Podemos actualizar estos términos para reflejar cambios del servicio o de la ley. La fecha de la última actualización aparece al inicio de esta página. El uso continuado de Educa después de un cambio implica la aceptación de la versión vigente.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">10. Ley aplicable</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Estos términos se rigen por las leyes de la República del Ecuador. Cualquier controversia se tramitará ante los jueces y tribunales competentes de Ecuador, sin perjuicio de los derechos que correspondan a consumidores o titulares de datos.
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
                    <a href="{{ route('terms') }}" class="text-emerald-900" aria-current="page">Términos y condiciones</a>
                    <a href="{{ route('cookies') }}" class="hover:text-emerald-900">Política de cookies</a>
                </nav>
                <p class="text-xs text-slate-400">&copy; {{ date('Y') }} Educa. Todos los derechos reservados.</p>
            </div>
        </footer>
    </body>
</html>
