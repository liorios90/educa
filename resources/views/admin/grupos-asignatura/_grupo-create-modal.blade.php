@php
    $mostrarCrearGrupo = $errors->any() && old('establecimiento_asignatura_id') !== null;
@endphp

<x-modal name="grupo-create" maxWidth="2xl" :show="$mostrarCrearGrupo" focusable>
    <form
        method="POST"
        action="{{ route('Admin.grupos-asignatura.store') }}"
        class="p-6"
        x-data="{
            asignaturaId: @js((string) old('establecimiento_asignatura_id', '')),
            seleccionados: @js(array_map(fn (mixed $id): int => (int) $id, (array) old('alumnos', []))),
            grados: @js($gradosAsignatura),
            ocupados: @js($ocupadosPorAsignatura),
            aulas: @js($aulasMatricula),
            busqueda: '',
            abierto: false,
            gradoId() {
                return this.grados[this.asignaturaId] ?? null;
            },
            coincide(texto) {
                const consulta = String(this.busqueda || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

                return consulta === '' || String(texto || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').includes(consulta);
            },
            ocupadoPor(alumnoId) {
                const dato = (this.ocupados[this.asignaturaId] || {})[String(alumnoId)];

                return dato ? dato.nombre : null;
            },
            alumnosDelGrado() {
                if (! this.gradoId()) {
                    return [];
                }

                return this.aulas
                    .filter((aula) => Number(aula.gradoId) === Number(this.gradoId()))
                    .flatMap((aula) => (aula.alumnos || []).map((alumno) => ({
                        id: Number(alumno.id),
                        nombre: alumno.nombre,
                        aula: aula.etiqueta,
                    })));
            },
            elegidos() {
                return this.alumnosDelGrado().filter((alumno) => this.seleccionados.includes(alumno.id));
            },
            disponibles() {
                return this.alumnosDelGrado().filter((alumno) =>
                    ! this.seleccionados.includes(alumno.id)
                    && this.ocupadoPor(alumno.id) === null
                    && this.coincide(alumno.nombre)
                );
            },
            agregar(id) {
                const valor = Number(id);

                if (! this.asignaturaId || this.ocupadoPor(valor) !== null) {
                    return;
                }

                if (! this.seleccionados.includes(valor)) {
                    this.seleccionados = [...this.seleccionados, valor];
                }

                this.busqueda = '';
                this.abierto = true;
            },
            quitar(id) {
                this.seleccionados = this.seleccionados.filter((actual) => Number(actual) !== Number(id));
            },
            elegirPrimero() {
                const primero = this.disponibles()[0];

                if (primero) {
                    this.agregar(primero.id);
                }
            },
            cambiarAsignatura() {
                this.seleccionados = [];
                this.busqueda = '';
                this.abierto = false;
            },
        }"
    >
        @csrf

        <h3 class="text-lg font-semibold text-slate-800">Nuevo grupo flotante</h3>
        <p class="mt-1 text-sm text-slate-500">
            Reúne alumnos de distintos paralelos del mismo grado. Elige la asignatura y busca a los alumnos en la lista.
        </p>

        <div class="mt-4 flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <x-input-label for="establecimiento_asignatura_id" value="Asignatura" />
                <select id="establecimiento_asignatura_id" name="establecimiento_asignatura_id" x-model="asignaturaId" x-on:change="cambiarAsignatura()" class="rounded-lg border-slate-300 text-sm shadow-sm">
                    <option value="">Selecciona</option>
                    @foreach ($asignaturas as $asignatura)
                        <option value="{{ $asignatura->id }}" @selected((string) old('establecimiento_asignatura_id') === (string) $asignatura->id)>{{ $asignatura->grado?->nombre }} · {{ $asignatura->nombre() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <x-input-label for="nombre" value="Nombre del grupo" />
                <x-text-input id="nombre" name="nombre" type="text" class="block w-full" value="{{ old('nombre') }}" placeholder="Grupo 1" />
            </div>
            <div class="flex flex-col gap-1">
                <x-input-label for="empleado_id" value="Docente" />
                <select id="empleado_id" name="empleado_id" class="rounded-lg border-slate-300 text-sm shadow-sm">
                    <option value="">Sin docente</option>
                    @foreach ($docentes as $docente)
                        <option value="{{ $docente->id }}" @selected((string) old('empleado_id') === (string) $docente->id)>{{ trim(($docente->persona->apellidos ?? '').' '.($docente->persona->nombres ?? '')) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <x-input-label for="busqueda-alumnos-grupo" value="Alumnos" />
                <div class="rounded-lg border border-slate-300 bg-white px-2 py-1 shadow-sm" x-bind:class="{ 'bg-slate-50': ! asignaturaId }" x-on:click.outside="abierto = false">
                    <div class="flex flex-wrap items-center gap-1">
                        <template x-for="alumno in elegidos()" :key="alumno.id">
                            <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                <span x-text="alumno.nombre"></span>
                                <button type="button" class="text-slate-500 hover:text-slate-800" x-on:click="quitar(alumno.id)" aria-label="Quitar">&times;</button>
                                <input type="hidden" name="alumnos[]" :value="alumno.id">
                            </span>
                        </template>
                        <input
                            id="busqueda-alumnos-grupo"
                            type="search"
                            class="min-w-[12rem] flex-1 border-0 bg-transparent py-1.5 text-sm focus:ring-0"
                            placeholder="Buscar y seleccionar varios"
                            autocomplete="off"
                            x-bind:disabled="! asignaturaId"
                            x-model="busqueda"
                            x-on:focus="if (asignaturaId) abierto = true"
                            x-on:keydown.enter.prevent="elegirPrimero()"
                        >
                    </div>
                    <div class="max-h-56 overflow-y-auto border-t border-slate-200 py-1" x-show="abierto && asignaturaId" x-cloak>
                        <template x-for="alumno in disponibles()" :key="alumno.id">
                            <button type="button" class="block w-full px-2 py-1.5 text-left text-sm text-slate-700 hover:bg-slate-50" x-on:click="agregar(alumno.id)">
                                <span x-text="alumno.nombre"></span>
                                <span class="block text-xs text-slate-500" x-text="alumno.aula"></span>
                            </button>
                        </template>
                        <p class="px-2 py-2 text-sm text-slate-500" x-show="disponibles().length === 0">No hay alumnos para mostrar.</p>
                    </div>
                </div>
                <p class="text-xs text-slate-500">
                    Puedes marcar varios y filtrar por nombre o apellido en la misma lista. Solo aparecen alumnos ya matriculados en un paralelo de este grado.
                </p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
            <x-primary-button>Crear grupo</x-primary-button>
        </div>
    </form>
</x-modal>
