<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Política de privacidad de Educa: cómo tratamos los datos personales de instituciones, docentes, estudiantes y familias.">

        <title>Política de privacidad — Educa</title>

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
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Política de privacidad</h1>
                    <p class="text-sm text-slate-500">Última actualización: 14 de octubre de 2026</p>
                </header>

                <p class="text-base leading-7 text-slate-600">
                    Esta política describe cómo Educa trata los datos personales de quienes usan la plataforma: instituciones educativas, directivos, docentes, empleados, estudiantes y familias. El tratamiento se rige por la Ley Orgánica de Protección de Datos Personales del Ecuador y su reglamento.
                </p>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">1. Responsable del tratamiento</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Educa es la plataforma tecnológica que permite a cada institución organizar su gestión académica. Cada establecimiento es responsable de los datos que registra de su comunidad educativa. Educa trata esos datos para prestar el servicio, con las medidas de seguridad y los accesos definidos por rol.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">2. Datos que recopilamos</h2>
                    <p class="text-base leading-7 text-slate-600">Según el rol y el uso de la plataforma, podemos tratar:</p>
                    <ul class="flex list-disc flex-col gap-2 pl-5 text-base leading-7 text-slate-600">
                        <li>Datos de cuenta: nombre, correo electrónico, contraseña cifrada, rol y establecimiento.</li>
                        <li>Datos de identificación y contacto: cédula o pasaporte, fecha de nacimiento, teléfonos y dirección.</li>
                        <li>Datos académicos: grados, paralelos, periodos, asignaturas, matrículas y demás información escolar que la institución registre.</li>
                        <li>Datos de representantes, docentes y personal vinculados a la institución.</li>
                        <li>Datos técnicos de sesión: rol activo, modalidad, jornada, periodo seleccionado, cookies de sesión y registros necesarios para el funcionamiento seguro del servicio.</li>
                    </ul>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">3. Para qué usamos los datos</h2>
                    <ul class="flex list-disc flex-col gap-2 pl-5 text-base leading-7 text-slate-600">
                        <li>Crear y administrar cuentas, autenticar usuarios y proteger el acceso.</li>
                        <li>Prestar la gestión académica, la comunicación entre roles y la operación del establecimiento.</li>
                        <li>Cumplir obligaciones legales y requerimientos de la autoridad educativa cuando corresponda.</li>
                        <li>Mejorar la estabilidad, la seguridad y el soporte de la plataforma.</li>
                    </ul>
                    <p class="text-base leading-7 text-slate-600">
                        No vendemos datos personales ni los usamos para publicidad de terceros.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">4. Datos de niñas, niños y adolescentes</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Educa trata datos de estudiantes menores de edad únicamente en el marco del servicio educativo de la institución. Ese tratamiento se realiza con la autorización del representante legal y según las finalidades académicas de cada establecimiento. Aplicamos controles de acceso por rol para limitar quién puede ver esta información.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">5. Destinatarios y encargados</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Los datos son accesibles para el personal autorizado de la institución, según su rol. También pueden tratarlos proveedores necesarios para hospedar, respaldar o enviar comunicaciones del servicio, bajo obligaciones de confidencialidad. No se ceden a terceros ajenos a estas finalidades, salvo obligación legal.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">6. Conservación</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Conservamos los datos mientras la cuenta o la relación con la institución esté activa y durante los plazos adicionales que exija la normativa educativa o de archivo. Después, se eliminan o se anonimizan cuando ya no sean necesarios.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">7. Tus derechos</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Puedes solicitar acceso, rectificación, actualización, eliminación, oposición, suspensión y portabilidad de tus datos personales, en los términos de la ley ecuatoriana. Para ejercerlos, contacta al administrador de tu institución a través de la plataforma. Si el requerimiento corresponde a Educa como proveedor, el administrador lo canalizará.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">8. Cookies y sesión</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Usamos cookies estrictamente necesarias para mantener la sesión, proteger el formulario contra envíos no autorizados y recordar preferencias de acceso. No utilizamos cookies de publicidad. El detalle está en la <a href="{{ route('cookies') }}" class="font-semibold text-emerald-900 underline hover:text-emerald-800">política de cookies</a>.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">9. Seguridad</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Las contraseñas se almacenan cifradas. El acceso a la información se limita por rol y por establecimiento. Aun así, ningún sistema es infalible: te pedimos cuidar tus credenciales y cerrar sesión en equipos compartidos.
                    </p>
                </section>

                <section class="flex flex-col gap-3">
                    <h2 class="text-xl font-bold text-slate-950">10. Cambios a esta política</h2>
                    <p class="text-base leading-7 text-slate-600">
                        Podemos actualizar esta política para reflejar cambios del servicio o de la ley. La fecha de la última actualización aparece al inicio de esta página. El uso continuado de Educa después de un cambio implica el conocimiento de la versión vigente.
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
                    <a href="{{ route('privacy') }}" class="text-emerald-900" aria-current="page">Política de privacidad</a>
                    <a href="{{ route('terms') }}" class="hover:text-emerald-900">Términos y condiciones</a>
                    <a href="{{ route('cookies') }}" class="hover:text-emerald-900">Política de cookies</a>
                </nav>
                <p class="text-xs text-slate-400">&copy; {{ date('Y') }} Educa. Todos los derechos reservados.</p>
            </div>
        </footer>
    </body>
</html>
