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
            @if (session('status') === 'programa-deleted')
                <p class="text-sm font-medium text-green-700">Programa eliminado.</p>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" x-data>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-800">Programas del periodo</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Puedes crear varios en {{ $periodo->nombre }}. Los alumnos siguen matriculados en su paralelo.
                        </p>
                    </div>
                    <x-primary-button type="button" x-on:click="$dispatch('open-modal', 'programa-create')">
                        Nuevo programa
                    </x-primary-button>
                </div>

                <div class="mt-5 flex flex-col gap-3">
                    @forelse ($programas as $programa)
                        @php
                            $miembros = $programa->membresias->pluck('alumno_id')->map(fn ($id) => (int) $id)->all();
                            $miembrosMatricula = $matriculas->filter(fn ($matricula) => in_array((int) $matricula->alumno_id, $miembros, true));
                            $ligadas = $programa->asignaturas->pluck('id')->map(fn ($id) => (int) $id)->all();
                            $asignaturasDisponibles = $propias->filter(
                                fn ($asignatura) => $asignatura->establecimiento_programa_id === null
                                    || (int) $asignatura->establecimiento_programa_id === (int) $programa->id
                            );
                        @endphp
                        <article
                            class="rounded-xl border border-slate-200 bg-slate-50 p-4"
                            x-data="{
                                seleccionados: @js($miembros),
                                editar: @js([
                                    'action' => route('Admin.programas.update', $programa),
                                    'programaId' => (int) $programa->id,
                                    'tipo' => $programa->tipo->value,
                                    'nombre' => $programa->nombre,
                                ]),
                                abrirAlumnos() {
                                    this.$dispatch('abrir-programa-alumnos', {
                                        origen: @js((string) $programa->id),
                                        titulo: @js($programa->nombre),
                                        seleccionados: this.seleccionados,
                                    });
                                },
                                abrirEditar() {
                                    this.$dispatch('abrir-programa-editar', this.editar);
                                },
                            }"
                            x-on:programa-alumnos-listos.window="if (String($event.detail.origen) === @js((string) $programa->id)) seleccionados = $event.detail.seleccionados"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-900">{{ $programa->nombre }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $programa->tipo->label() }} · {{ $periodo->nombre }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <x-secondary-button type="button" x-on:click="abrirEditar()">
                                        Editar
                                    </x-secondary-button>
                                    <form method="POST" action="{{ route('Admin.programas.destroy', $programa) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-danger-button>Eliminar</x-danger-button>
                                    </form>
                                </div>
                            </div>

                            <form
                                id="programa-alumnos-{{ $programa->id }}"
                                method="POST"
                                action="{{ route('Admin.programas.alumnos.update', $programa) }}"
                                class="mt-4"
                            >
                                @csrf
                                @method('PUT')
                                <template x-for="id in seleccionados" :key="id">
                                    <input type="hidden" name="alumnos[]" :value="id">
                                </template>
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4">
                                    <div class="flex flex-col gap-1">
                                        <h4 class="text-sm font-semibold text-slate-800">Alumnos del programa</h4>
                                        @forelse ($miembrosMatricula as $matricula)
                                            <p class="text-sm text-slate-500">
                                                {{ trim(($matricula->alumno->persona->apellidos ?? '').' '.($matricula->alumno->persona->nombres ?? '')) }}
                                                · {{ $matricula->aula->etiqueta() }}
                                            </p>
                                        @empty
                                            <p class="text-sm text-slate-500">Todavía no hay alumnos en este programa.</p>
                                        @endforelse
                                    </div>
                                    <x-secondary-button type="button" x-on:click="abrirAlumnos()">Alumnos</x-secondary-button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('Admin.programas.asignaturas.update', $programa) }}" class="mt-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4">
                                @csrf
                                @method('PUT')
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-800">Asignaturas del programa</h4>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Usa asignaturas propias. Al guardarlas aquí dejan de aparecer en la libreta nacional.
                                    </p>
                                </div>
                                @forelse ($asignaturasDisponibles as $asignatura)
                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                        <input type="checkbox" name="asignaturas[]" value="{{ $asignatura->id }}" @checked(in_array((int) $asignatura->id, $ligadas, true)) class="rounded border-slate-300">
                                        <span>{{ $asignatura->grado?->nombre }} · {{ $asignatura->nombre() }}</span>
                                    </label>
                                @empty
                                    <p class="text-sm text-slate-500">Crea primero la asignatura propia en Asignaturas.</p>
                                @endforelse
                                <div>
                                    <x-primary-button>Guardar asignaturas</x-primary-button>
                                </div>
                            </form>

                            @foreach ($programa->asignaturas as $asignatura)
                                @php
                                    $inscritos = $inscripciones->get($asignatura->id, collect())->pluck('alumno_id')->map(fn ($id) => (int) $id)->all();
                                    $candidatos = $matriculas->filter(function ($matricula) use ($asignatura, $miembros) {
                                        return in_array((int) $matricula->alumno_id, $miembros, true)
                                            && (int) $matricula->aula->establecimientoGrado?->grado_id === (int) $asignatura->grado_id;
                                    });
                                @endphp
                                <form method="POST" action="{{ route('Admin.programas.inscripciones.update', [$programa, $asignatura]) }}" class="mt-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-800">{{ $asignatura->nombre() }}</h4>
                                        <p class="mt-1 text-xs text-slate-500">{{ $asignatura->grado?->nombre }} · escala de 1 a 7. Solo los alumnos marcados cursan esta materia.</p>
                                    </div>
                                    @forelse ($candidatos as $matricula)
                                        <label class="flex items-center gap-2 text-sm text-slate-700">
                                            <input type="checkbox" name="alumnos[]" value="{{ $matricula->alumno_id }}" @checked(in_array((int) $matricula->alumno_id, $inscritos, true)) class="rounded border-slate-300">
                                            <span>{{ trim(($matricula->alumno->persona->apellidos ?? '').' '.($matricula->alumno->persona->nombres ?? '')) }} · {{ $matricula->aula->etiqueta() }}</span>
                                        </label>
                                    @empty
                                        <p class="text-sm text-slate-500">Ningún alumno del programa está matriculado en este grado.</p>
                                    @endforelse
                                    <div>
                                        <x-primary-button>Guardar inscripción</x-primary-button>
                                    </div>
                                </form>
                            @endforeach
                        </article>
                    @empty
                        <p class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                            No hay programas en este periodo.
                        </p>
                    @endforelse
                </div>
            </section>

            @include('admin.programas._create-modal')
            @include('admin.programas._edit-modal')
            @include('admin.programas._alumnos-modal')
        </div>
    </div>
</x-app-layout>
