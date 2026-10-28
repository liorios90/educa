<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Notas</h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $periodo?->nombre ?? 'Sin periodo activo' }}
                @if ($aula)
                    · {{ $aula->etiqueta() }}
                @endif
                @if ($puedeElegirAlumno && $alumno)
                    · {{ trim(($alumno->persona?->apellidos ?? '').' '.($alumno->persona?->nombres ?? '')) }}
                @endif
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">
            @if ($alumnos->isEmpty())
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    No encontramos un estudiante vinculado a tu cuenta. Comunícate con el colegio para revisar el registro.
                </p>
            @else
                @if ($puedeElegirAlumno)
                    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-slate-700">
                            {{ $alumno === null ? 'Selecciona el hijo cuyas notas quieres ver' : 'Cambiar de hijo' }}
                        </p>
                        <div class="mt-3 flex flex-col gap-3">
                            @foreach ($alumnos as $opcion)
                                @php
                                    $nombreHijo = trim(($opcion->persona?->apellidos ?? '').' '.($opcion->persona?->nombres ?? ''));
                                    $hijoSeleccionado = (int) $alumno?->id === (int) $opcion->id;
                                @endphp
                                <a
                                    href="{{ route('notas.index', ['alumno_id' => $opcion->id]) }}"
                                    class="flex w-full items-center justify-between rounded-xl border bg-white px-4 py-3 text-left shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 {{ $hijoSeleccionado ? 'border-indigo-400 ring-1 ring-indigo-200' : 'border-slate-200' }}"
                                >
                                    <span class="text-sm font-semibold text-slate-800">{{ $nombreHijo }}</span>
                                    <span class="text-xs {{ $hijoSeleccionado ? 'font-medium text-indigo-600' : 'text-slate-400' }}">
                                        {{ $hijoSeleccionado ? 'Consultando' : 'Ver notas' }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($alumno === null)
                    @unless ($puedeElegirAlumno)
                        <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                            Selecciona el hijo cuyas notas quieres ver.
                        </p>
                    @endunless
                @elseif ($aula === null || $libreta === null)
                    <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                        {{ trim(($alumno->persona?->apellidos ?? '').' '.($alumno->persona?->nombres ?? '')) }} no tiene una matrícula en un periodo activo.
                    </p>
                @else
                    @include('libreta.tabla', [
                        'libreta' => $libreta,
                        'mensajeVacio' => 'No hay materias con calificaciones para mostrar.',
                    ])
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
