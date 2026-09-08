--- ### Código para `resources/views/landing.blade.php`: ```html
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduTrack OS - Gestión de Notas y Alumnos Sencilla y Rápida</title> <!-- Tipografía Geist -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"> <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Geist', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            900: '#1e1b4b',
                        }
                    }
                }
            }
        }
    </script> {{-- Si compilas con Vite en Laravel, puedes usar en su lugar: --}} {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
</head>

<body class="bg-[#f8f9ff] text-slate-900 font-sans antialiased selection:bg-brand-600 selection:text-white"> <!-- ========================================== 1. NAVBAR MINIMALISTA =========================================== -->
    <nav class="sticky top-0 z-50 bg-[#f8f9ff]/80 backdrop-blur-md border-b border-slate-200/60">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between"> <!-- Brand Logo -->
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center text-white shadow-md shadow-brand-500/20"> <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z" />
                    </svg> </div> <span class="text-lg font-bold tracking-tight text-slate-900"> EduTrack<span class="text-brand-600">.OS</span> </span>
            </div> <!-- Links -->
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600"> <a href="#caracteristicas" class="hover:text-brand-600 transition-colors">Características</a> <a href="#plataforma" class="hover:text-brand-600 transition-colors">Plataforma</a> <a href="#institucional" class="hover:text-brand-600 transition-colors">Institucional</a> </div> <!-- Actions -->
            <div class="flex items-center gap-4"> <a href="/login" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors"> Iniciar Sesión </a> <a href="#probar" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm shadow-brand-600/20 transition-all"> Probar Gratis &rarr; </a> </div>
        </div>
    </nav> <!-- ========================================== 2. HERO SECTION =========================================== -->
    <section class="pt-14 pb-20 overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 text-center"> <!-- Píldora de lanzamiento -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white border border-slate-200/80 shadow-xs text-xs font-semibold text-slate-700 mb-8 hover:border-brand-300 transition-colors"> <span class="w-2 h-2 rounded-full bg-amber-400"></span> <span>✨ Laravel 13 &bull; Nueva Generación Académica</span> <span class="text-slate-400">&rsaquo;</span> </div> <!-- Titular Principal con gradiente llamativo -->
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 max-w-4xl mx-auto leading-[1.15]"> Gestionar notas y alumnos <br class="hidden sm:inline" /> <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-600 via-indigo-600 to-teal-500"> nunca fue tan simple </span> </h1>
            <p class="mt-6 text-base sm:text-lg text-slate-600 max-w-2xl mx-auto font-normal leading-relaxed"> La plataforma moderna de libro de calificaciones, asistencia biométrica y expedientes 360° para colegios e institutos que buscan excelencia. </p> <!-- Botones de Acción -->
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3.5"> <a href="#probar" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-lg shadow-brand-600/25 transition-all"> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg> Comenzar Ahora Gratis </a> <a href="#plataforma" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200/90 rounded-xl shadow-xs transition-all"> <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg> Ver Demostración (2 min) </a> </div> <!-- Social Proof compacto -->
            <div class="mt-6 flex items-center justify-center gap-2 text-xs text-slate-500">
                <div class="flex -space-x-2"> <span class="w-6 h-6 rounded-full bg-slate-300 border-2 border-white inline-block"></span> <span class="w-6 h-6 rounded-full bg-slate-400 border-2 border-white inline-block"></span> <span class="w-6 h-6 rounded-full bg-slate-500 border-2 border-white inline-block"></span> </div> <span><strong class="text-slate-800">+150 instituciones</strong> gestionan más de 450k notas cada día</span>
            </div> <!-- ========================================== MOCKUP FLOTANTE (CARD DEL SISTEMA) =========================================== -->
            <div id="plataforma" class="mt-12 max-w-4xl mx-auto bg-white rounded-2xl border border-slate-200/80 shadow-2xl shadow-indigo-100/70 p-6 text-left"> <!-- Header del Mockup -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-100 gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center"> <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg> </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Álgebra Lineal & Cálculo II</h3>
                            <p class="text-xs text-slate-500">Sección A-1 &bull; Promedio: <strong class="text-brand-600">18.2</strong></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-xs"> <span class="px-2.5 py-1 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 font-medium flex items-center gap-1"> <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg> Actas al Día </span> <span class="px-2 py-1 rounded-md bg-slate-100 font-mono text-[11px] text-slate-600"> Octane 0.12ms </span> </div>
                </div> <!-- 3 KPIs Rápidos -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 py-5 border-b border-slate-100">
                    <div class="p-3.5 rounded-xl bg-slate-50/70 border border-slate-100"> <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Aprobados</span>
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">94.2%</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50/70 border border-slate-100"> <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Asistencia Biometría</span>
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">98.6%</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50/70 border border-slate-100"> <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Promedio General</span>
                        <div class="text-2xl font-bold text-brand-600 mt-0.5">17.8<span class="text-xs text-slate-400 font-normal">/20</span></div>
                    </div>
                </div> <!-- Lista de Alumnos Sintética -->
                <div class="divide-y divide-slate-100 text-xs mt-1">
                    <div class="py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full bg-brand-100 text-brand-700 font-bold flex items-center justify-center text-[10px]">MR</div> <span class="font-semibold text-slate-800">Mateo Rossi Calderón</span>
                        </div>
                        <div class="flex items-center gap-6"> <span class="text-slate-400 hidden sm:inline">Parcial: 18.5</span> <span class="font-bold text-brand-600 text-sm">18.43</span> <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">Sobresaliente</span> </div>
                    </div>
                    <div class="py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full bg-purple-100 text-purple-700 font-bold flex items-center justify-center text-[10px]">VS</div> <span class="font-semibold text-slate-800">Valentina Silva Morán</span>
                        </div>
                        <div class="flex items-center gap-6"> <span class="text-slate-400 hidden sm:inline">Parcial: 19.0</span> <span class="font-bold text-brand-600 text-sm">18.33</span> <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">Sobresaliente</span> </div>
                    </div>
                    <div class="py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full bg-amber-100 text-amber-700 font-bold flex items-center justify-center text-[10px]">CM</div> <span class="font-semibold text-slate-800">Carlos Mendoza Vaca</span>
                        </div>
                        <div class="flex items-center gap-6"> <span class="text-slate-400 hidden sm:inline">Parcial: 16.0</span> <span class="font-bold text-slate-800 text-sm">15.70</span> <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-800">Notable</span> </div>
                    </div>
                </div>
            </div>
        </div>
    </section> <!-- ========================================== 3. SECCIÓN: 3 VALORES ESENCIALES =========================================== -->
    <section id="caracteristicas" class="py-20 bg-white border-y border-slate-200/70">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16"> <span class="text-xs font-bold uppercase tracking-wider text-brand-600">Simplicidad Extrema</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 tracking-tight"> Potencia institucional sin complejidad </h2>
                <p class="mt-3 text-base text-slate-600"> Diseñado para directores, docentes y secretarios académicos que valoran su tiempo. </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8"> <!-- Tarjeta 1 -->
                <div class="p-8 rounded-2xl bg-[#f8f9ff] border border-slate-200/80 hover:border-brand-300 transition-all">
                    <div class="w-11 h-11 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center mb-6"> <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg> </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">Cálculo de Notas en 1 Clic</h3>
                    <p class="text-sm text-slate-600 leading-relaxed"> Olvídate de las hojas de cálculo manuales. Ponderaciones automáticas, reglas polinómicas institucionales y cierre digital hermético con firma electrónica. </p>
                </div> <!-- Tarjeta 2 -->
                <div class="p-8 rounded-2xl bg-[#f8f9ff] border border-slate-200/80 hover:border-brand-300 transition-all">
                    <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-6"> <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg> </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">Expediente del Alumno 360°</h3>
                    <p class="text-sm text-slate-600 leading-relaxed"> Toda la historia en una sola ficha: asistencia biométrica en tiempo real, alertas tempranas de deserción escolar y reportes psicopedagógicos protegidos. </p>
                </div> <!-- Tarjeta 3 -->
                <div class="p-8 rounded-2xl bg-[#f8f9ff] border border-slate-200/80 hover:border-brand-300 transition-all">
                    <div class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center mb-6"> <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg> </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">Rápido como un rayo</h3>
                    <p class="text-sm text-slate-600 leading-relaxed"> Construido con Laravel 13 y Octane para responder en menos de 0.4s incluso durante las horas punta de cierre de ciclo escolar y matrícula masiva. </p>
                </div>
            </div>
        </div>
    </section> <!-- ========================================== 4. CTA FINAL (BLOQUE DIRECTO) =========================================== -->
    <section id="probar" class="py-20">
        <div class="max-w-5xl mx-auto px-6">
            <div class="bg-gradient-to-r from-brand-700 via-indigo-700 to-brand-600 rounded-3xl p-10 sm:p-14 text-center text-white shadow-2xl shadow-indigo-500/20"> <span class="text-xs font-bold uppercase tracking-wider text-emerald-300">Migración asistida en 72 horas</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold mt-3 tracking-tight"> Moderniza tu institución hoy mismo </h2>
                <p class="mt-3 text-sm sm:text-base text-indigo-100 max-w-xl mx-auto"> Únete a más de 150 instituciones que transformaron su control de notas y retención estudiantil. </p> <!-- Input rápido de registro -->
                <form action="#" method="POST" class="mt-8 max-w-md mx-auto flex flex-col sm:flex-row gap-3"> @csrf <input type="email" placeholder="director@instituto.edu" class="flex-1 px-4 py-3 rounded-xl text-sm text-slate-800 bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-white" required> <button type="submit" class="px-6 py-3 rounded-xl bg-white hover:bg-slate-100 text-brand-700 font-semibold text-sm transition-all shadow-md"> Probar 14 Días </button> </form>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-6 text-xs text-indigo-100"> <span class="flex items-center gap-1.5"> <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg> Sin tarjetas requeridas </span> <span class="flex items-center gap-1.5"> <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg> Soporte y onboarding incluido </span> </div>
            </div>
        </div>
    </section> <!-- ========================================== 5. FOOTER =========================================== -->
    <footer id="institucional" class="border-t border-slate-200/80 bg-white py-10 text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2"> <span class="font-bold text-slate-900 text-sm">EduTrack<span class="text-brand-600">.OS</span></span> <span>&bull; &copy; {{ date('Y') }} EduTrack OS. Todos los derechos reservados.</span> </div>
            <div class="flex items-center gap-6 text-slate-600"> <a href="#" class="hover:text-brand-600 transition-colors">Términos</a> <a href="#" class="hover:text-brand-600 transition-colors">Privacidad</a> <a href="#" class="hover:text-brand-600 transition-colors">Seguridad ISO 27001</a> <a href="#" class="hover:text-brand-600 transition-colors">Contacto</a> </div>
        </div>
    </footer>
</body>

</html> ```