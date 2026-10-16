<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Programas
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $establecimiento->nombre }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'programa-created')
                <p class="text-sm font-medium text-green-700">Programa creado.</p>
            @endif
            @if (session('status') === 'programa-updated')
                <p class="text-sm font-medium text-green-700">Programa actualizado.</p>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-slate-800">Bachillerato Internacional</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Solo entran los alumnos inscritos, y cada uno cursa las materias que eligió. Esas notas usan la escala de 1 a 7 y no salen en la libreta nacional ni en el certificado de promoción. El alumno sigue matriculado en su paralelo.
                </p>
                @if ($programa === null)
                    <form method="POST" action="{{ route('Admin.programas.store') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                        @csrf
                        <div class="flex flex-1 flex-col gap-1">
                            <x-input-label for="tipo" value="Tipo" />
                            <select id="tipo" name="tipo" class="rounded-lg border-slate-300 text-sm shadow-sm">
                                @foreach ($tipos as $tipo)
                                    <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-[2] flex-col gap-1">
                            <x-input-label for="nombre" value="Nombre" />
                            <x-text-input id="nombre" name="nombre" type="text" class="block w-full" value="Bachillerato Internacional" />
                        </div>
                        <x-primary-button>Crear programa</x-primary-button>
                    </form>
                @else
                    <p class="mt-3 text-sm font-medium text-slate-800">{{ $programa->nombre }} · {{ $periodo->nombre }}</p>
                @endif
            </section>

            @if ($programa !== null)
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800">Alumnos del programa</h3>
                    <p class="mt-1 text-sm text-slate-500">Pueden venir de distintos paralelos. Primero deben estar matriculados.</p>
                    <form method="POST" action="{{ route('Admin.programas.alumnos.update', $programa) }}" class="mt-4 flex flex-col gap-3">
                        @csrf
                        @method('PUT')
                        @php
                            $miembros = $programa->membresias->pluck('alumno_id')->map(fn ($id) => (int) $id)->all();
                        @endphp
                        @forelse ($matriculas as $matricula)
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="alumnos[]" value="{{ $matricula->alumno_id }}" @checked(in_array((int) $matricula->alumno_id, $miembros, true)) class="rounded border-slate-300">
                                <span>{{ trim(($matricula->alumno->persona->apellidos ?? '').' '.($matricula->alumno->persona->nombres ?? '')) }} · {{ $matricula->aula->etiqueta() }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-slate-500">No hay alumnos matriculados en esta jornada.</p>
                        @endforelse
                        <x-primary-button>Guardar alumnos</x-primary-button>
                    </form>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800">Asignaturas del programa</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Usa asignaturas propias, por ejemplo Matemáticas AA NS. Al guardarlas aquí dejan de aparecer en la libreta nacional.
                    </p>
                    <form method="POST" action="{{ route('Admin.programas.asignaturas.update', $programa) }}" class="mt-4 flex flex-col gap-3">
                        @csrf
                        @method('PUT')
                        @php
                            $ligadas = $programa->asignaturas->pluck('id')->map(fn ($id) => (int) $id)->all();
                        @endphp
                        @forelse ($propias as $asignatura)
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="asignaturas[]" value="{{ $asignatura->id }}" @checked(in_array((int) $asignatura->id, $ligadas, true)) class="rounded border-slate-300">
                                <span>{{ $asignatura->grado?->nombre }} · {{ $asignatura->nombre() }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-slate-500">Crea primero la asignatura propia en Asignaturas.</p>
                        @endforelse
                        <x-primary-button>Guardar asignaturas</x-primary-button>
                    </form>
                </section>

                @foreach ($programa->asignaturas as $asignatura)
                    @php
                        $inscritos = $inscripciones->get($asignatura->id, collect())->pluck('alumno_id')->map(fn ($id) => (int) $id)->all();
                        $candidatos = $matriculas->filter(function ($matricula) use ($asignatura, $miembros) {
                            return in_array((int) $matricula->alumno_id, $miembros, true)
                                && (int) $matricula->aula->establecimientoGrado?->grado_id === (int) $asignatura->grado_id;
                        });
                    @endphp
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-800">{{ $asignatura->nombre() }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $asignatura->grado?->nombre }} · escala de 1 a 7. Solo los alumnos marcados cursan esta materia.</p>
                        <form method="POST" action="{{ route('Admin.programas.inscripciones.update', [$programa, $asignatura]) }}" class="mt-4 flex flex-col gap-3">
                            @csrf
                            @method('PUT')
                            @forelse ($candidatos as $matricula)
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="alumnos[]" value="{{ $matricula->alumno_id }}" @checked(in_array((int) $matricula->alumno_id, $inscritos, true)) class="rounded border-slate-300">
                                    <span>{{ trim(($matricula->alumno->persona->apellidos ?? '').' '.($matricula->alumno->persona->nombres ?? '')) }} · {{ $matricula->aula->etiqueta() }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-slate-500">Ningún alumno del programa está matriculado en este grado.</p>
                            @endforelse
                            <x-primary-button>Guardar inscripción</x-primary-button>
                        </form>
                    </section>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
