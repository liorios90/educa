<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Mejoras de calificaciones</h2>
            <p class="mt-1 text-sm text-slate-500">Registra la solicitud, el refuerzo pedagógico y la evaluación adicional de una nota sumativa.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'mejora-calificacion-registrada')
                <p class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">La mejora se registró y la libreta utilizará la nueva calificación.</p>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-medium">No se pudo registrar la mejora.</p>
                    <ul class="mt-2 grid gap-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($aulas === [])
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">No tienes aulas asignadas en un periodo activo.</p>
            @elseif (! $contexto)
                <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">
                    <p>Primero selecciona el aula, la asignatura y el periodo en la pantalla de calificación.</p>
                    <div class="flex flex-wrap gap-3">
                        <a class="font-medium text-indigo-700 hover:text-indigo-900" href="{{ route('Docente.examenes.index') }}">Ir a calificar exámenes</a>
                        <a class="font-medium text-indigo-700 hover:text-indigo-900" href="{{ route('Docente.proyectos.index') }}">Ir a calificar proyectos</a>
                    </div>
                </div>
            @elseif (! $contexto->permiteIngresarCalificaciones())
                <p class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ \App\Auth\DocenteContexto::MENSAJE_PERIODO_CERRADO }}
                </p>
            @else
                <section class="grid gap-2 rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-700 sm:grid-cols-3">
                    <p><span class="font-medium">Aula:</span> {{ $contexto->aula->etiqueta() }}</p>
                    <p><span class="font-medium">Asignatura:</span> {{ $contexto->asignatura->nombre() }}</p>
                    <p><span class="font-medium">Normativa aplicada:</span> {{ $normativa === '2024' ? 'Acuerdo 2024-00031-A' : 'Acuerdo 2026-00082-A' }}</p>
                </section>

                @if ($calificaciones->isEmpty())
                    <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">No hay notas sumativas numéricas registradas para este periodo.</p>
                @else
                    <div class="grid gap-5">
                        @foreach ($calificaciones as $calificacion)
                            @php
                                $nombre = trim(($calificacion->alumno->persona->apellidos ?? '').' '.($calificacion->alumno->persona->nombres ?? ''));
                                $tipoMejora = $calificacion->tipo_mejora_disponible;
                            @endphp
                            <article class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
                                <div class="grid content-start gap-2">
                                    <h3 class="font-semibold text-slate-900">{{ $nombre ?: 'Estudiante' }}</h3>
                                    <p class="text-sm text-slate-600">{{ $calificacion->actividad->nombre }} · Nota original: <span class="font-semibold">{{ number_format((float) $calificacion->nota, 2) }}</span></p>
                                    @if ($calificacion->mejora)
                                        <p class="text-sm text-green-800">Mejora registrada: {{ number_format((float) $calificacion->mejora->nota_final, 2) }} (original: {{ number_format((float) $calificacion->mejora->nota_inicial, 2) }})</p>
                                    @elseif ($tipoMejora === 'directa')
                                        <p class="text-sm text-indigo-700">Mejora directa · promedio con máximo 9,00.</p>
                                    @elseif ($tipoMejora === 'refuerzo' && $normativa === '2026')
                                        <p class="text-sm text-indigo-700">Mejora con refuerzo pedagógico · nueva nota hasta 7,00.</p>
                                    @elseif ($tipoMejora === 'refuerzo')
                                        <p class="text-sm text-indigo-700">Mejora con refuerzo pedagógico y evaluación adicional.</p>
                                    @else
                                        <p class="text-sm text-slate-500">Esta nota está fuera de los rangos de mejora.</p>
                                    @endif
                                </div>

                                @if (! $calificacion->mejora && $tipoMejora)
                                    <form method="POST" action="{{ route('Docente.mejora-calificaciones.store') }}" class="grid gap-4 sm:grid-cols-2">
                                        @csrf
                                        <input type="hidden" name="calificacion_id" value="{{ $calificacion->id }}">
                                        <div>
                                            <x-input-label :for="'notificacion-'.$calificacion->id" value="Fecha en que se notificó la calificación" />
                                            <x-text-input :id="'notificacion-'.$calificacion->id" name="fecha_notificacion" type="date" :value="old('fecha_notificacion', today()->toDateString())" required class="mt-1 block w-full" />
                                        </div>
                                        <div>
                                            <x-input-label :for="'solicitud-'.$calificacion->id" value="Fecha de solicitud" />
                                            <x-text-input :id="'solicitud-'.$calificacion->id" name="fecha_solicitud" type="date" :value="old('fecha_solicitud', today()->toDateString())" required class="mt-1 block w-full" />
                                        </div>
                                        <div>
                                            <x-input-label :for="'evaluacion-'.$calificacion->id" value="Fecha de evaluación adicional" />
                                            <x-text-input :id="'evaluacion-'.$calificacion->id" name="fecha_evaluacion" type="date" :value="old('fecha_evaluacion', today()->toDateString())" required class="mt-1 block w-full" />
                                        </div>
                                        <div>
                                            <x-input-label :for="'referencia-'.$calificacion->id" value="Referencia de la solicitud escrita" />
                                            <x-text-input :id="'referencia-'.$calificacion->id" name="referencia_solicitud" type="text" maxlength="255" placeholder="N.º de documento o referencia" required class="mt-1 block w-full" :value="old('referencia_solicitud')" />
                                        </div>
                                        @if ($tipoMejora === 'refuerzo' && $requiereNotaRefuerzo)
                                            <div>
                                                <x-input-label :for="'refuerzo-'.$calificacion->id" value="Nota del refuerzo pedagógico" />
                                                <x-text-input :id="'refuerzo-'.$calificacion->id" name="nota_refuerzo" type="number" min="0" max="10" step="0.01" required class="mt-1 block w-full" :value="old('nota_refuerzo')" />
                                            </div>
                                        @endif
                                        <div>
                                            <x-input-label :for="'adicional-'.$calificacion->id" value="Nota de la evaluación adicional" />
                                            <x-text-input :id="'adicional-'.$calificacion->id" name="nota_adicional" type="number" min="0" max="10" step="0.01" required class="mt-1 block w-full" :value="old('nota_adicional')" />
                                        </div>
                                        <div class="flex items-end sm:col-span-2 sm:justify-end">
                                            <x-primary-button>Registrar mejora</x-primary-button>
                                        </div>
                                    </form>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
