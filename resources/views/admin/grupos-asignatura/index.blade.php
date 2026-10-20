<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                {{ $mostrarGruposFlotantes ? 'Grupos flotantes' : 'Matricular alumnos' }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $establecimiento->nombre }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            <nav class="flex flex-wrap gap-3" aria-label="Opciones de grupos de clase">
                @if (! $mostrarGruposFlotantes)
                <a href="{{ route('Admin.grupos-asignatura.index') }}" @class(['rounded-lg px-4 py-2 text-sm font-medium', 'bg-slate-900 text-white' => ! $mostrarGruposFlotantes, 'border border-slate-200 bg-white text-slate-700' => $mostrarGruposFlotantes])>Matricular alumnos</a>
                @endif
                <a href="{{ route('Admin.grupos-asignatura.flotantes') }}" @class(['rounded-lg px-4 py-2 text-sm font-medium', 'bg-slate-900 text-white' => $mostrarGruposFlotantes, 'border border-slate-200 bg-white text-slate-700' => ! $mostrarGruposFlotantes])>Grupos flotantes</a>
            </nav>
            @if (session('status') === 'matricula-created')
                <p class="text-sm font-medium text-green-700">
                    @if ((int) session('matriculas_creadas', 1) === 1)
                        Alumno matriculado en el paralelo.
                    @else
                        Se matricularon {{ (int) session('matriculas_creadas') }} alumnos en el paralelo.
                    @endif
                </p>
            @endif
            @if (session('status') === 'matricula-deleted')
                <p class="text-sm font-medium text-green-700">Alumno quitado del paralelo.</p>
            @endif
            @if (session('status') === 'grupo-created')
                <p class="text-sm font-medium text-green-700">Grupo creado. La asignatura queda como flotante.</p>
            @endif
            @if (session('status') === 'grupo-updated')
                <p class="text-sm font-medium text-green-700">Alumnos del grupo actualizados.</p>
            @endif
            @if (session('status') === 'grupo-deleted')
                <p class="text-sm font-medium text-green-700">Grupo eliminado. Las notas siguen en la asignatura del grado.</p>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (! $mostrarGruposFlotantes)
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" x-data>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-800">Matrícula del paralelo</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Cada alumno tiene un solo paralelo en {{ $periodo->nombre }}.
                        </p>
                    </div>
                    <x-primary-button type="button" x-on:click="$dispatch('open-modal', 'matricula-create')">
                        Nueva matrícula
                    </x-primary-button>
                </div>

                @include('admin.grupos-asignatura._matricula-form')

                <div class="mt-5 flex flex-col gap-3">
                    @forelse ($aulasMatricula as $aulaVista)
                        <article
                            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4"
                            x-data="{ aula: @js(['id' => $aulaVista['id'], 'etiqueta' => $aulaVista['etiqueta'], 'alumnos' => $aulaVista['alumnos']]) }"
                        >
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $aulaVista['etiqueta'] }}</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ count($aulaVista['alumnos']) === 1 ? '1 alumno' : count($aulaVista['alumnos']).' alumnos' }}
                                </p>
                            </div>
                            <x-secondary-button type="button" x-on:click="$dispatch('abrir-aula-alumnos', aula)">
                                Alumnos
                            </x-secondary-button>
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">No hay paralelos en este periodo.</p>
                    @endforelse
                </div>

                @include('admin.grupos-asignatura._aula-alumnos-modal')
            </section>
            @else
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" x-data>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-800">Grupos flotantes</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Educación Física, Inglés u otra asignatura que reúne alumnos de distintos paralelos.
                        </p>
                    </div>
                    <x-primary-button type="button" x-on:click="$dispatch('open-modal', 'grupo-create')">
                        Nuevo grupo flotante
                    </x-primary-button>
                </div>

                <div class="mt-5 flex flex-col gap-3">
            @forelse ($grupos as $grupo)
                @php
                    $elegibles = $matriculas->filter(fn ($matricula) => (int) $matricula->aula->establecimientoGrado?->grado_id === (int) $grupo->asignatura->grado_id);
                    $miembros = $grupo->miembros->pluck('alumno_id')->map(fn ($id) => (int) $id)->all();
                    $miembrosPorAula = $elegibles
                        ->filter(fn ($matricula) => in_array((int) $matricula->alumno_id, $miembros, true))
                        ->groupBy(fn ($matricula) => $matricula->aula->etiqueta());
                @endphp
                <article
                    class="rounded-xl border border-slate-200 bg-slate-50 p-4"
                    x-data="{
                        seleccionados: @js($miembros),
                        abrirAlumnos() {
                            this.$dispatch('abrir-grupo-alumnos', {
                                origen: @js((string) $grupo->id),
                                gradoId: @js((int) $grupo->asignatura->grado_id),
                                titulo: @js(trim(($grupo->asignatura->grado?->nombre ?? '').' · '.$grupo->asignatura->nombre())),
                                seleccionados: this.seleccionados,
                                ocupados: @js($ocupadosPorAsignatura[(string) $grupo->establecimiento_asignatura_id] ?? []),
                            });
                        },
                    }"
                    x-on:grupo-alumnos-listos.window="if (String($event.detail.origen) === @js((string) $grupo->id)) seleccionados = $event.detail.seleccionados"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ $grupo->nombre }}</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $grupo->asignatura->grado?->nombre }} · {{ $grupo->asignatura->nombre() }}
                                @if ($grupo->docente?->persona)
                                    · {{ trim($grupo->docente->persona->apellidos.' '.$grupo->docente->persona->nombres) }}
                                @endif
                            </p>
                        </div>
                        <form method="POST" action="{{ route('Admin.grupos-asignatura.destroy', $grupo) }}">
                            @csrf
                            @method('DELETE')
                            <x-danger-button>Eliminar grupo</x-danger-button>
                        </form>
                    </div>

                    <form
                        id="grupo-alumnos-{{ $grupo->id }}"
                        method="POST"
                        action="{{ route('Admin.grupos-asignatura.alumnos.update', $grupo) }}"
                        class="mt-4 flex flex-col gap-3"
                    >
                        @csrf
                        @method('PUT')
                        <template x-for="id in seleccionados" :key="id">
                            <input type="hidden" name="alumnos[]" :value="id">
                        </template>
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4">
                            <div class="flex flex-col gap-1">
                                <h4 class="text-sm font-semibold text-slate-800">Alumnos del grupo</h4>
                                @forelse ($miembrosPorAula as $etiqueta => $deAula)
                                    <p class="text-sm text-slate-500">{{ $etiqueta }} · {{ $deAula->count() === 1 ? '1 alumno' : $deAula->count().' alumnos' }}</p>
                                @empty
                                    <p class="text-sm text-slate-500">Todavía no hay alumnos en este grupo.</p>
                                @endforelse
                            </div>
                            <x-secondary-button type="button" x-on:click="abrirAlumnos()">Alumnos</x-secondary-button>
                        </div>
                    </form>
                </article>
            @empty
                    <p class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                        No hay grupos flotantes en este periodo.
                    </p>
            @endforelse
                </div>
            </section>

            @include('admin.grupos-asignatura._grupo-create-modal')
            @include('admin.grupos-asignatura._grupo-alumnos-modal')
            @endif
        </div>
    </div>
</x-app-layout>
