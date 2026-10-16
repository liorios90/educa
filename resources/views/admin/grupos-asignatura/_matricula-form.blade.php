@php
    $alumnosSelector = $alumnosDisponibles
        ->map(fn ($alumno): array => [
            'id' => (int) $alumno->id,
            'nombre' => trim(($alumno->persona->apellidos ?? '').' '.($alumno->persona->nombres ?? '')),
        ])
        ->values()
        ->all();
    $seleccionadosAnteriores = array_map(fn (mixed $id): int => (int) $id, (array) old('alumnos', []));
    $mostrarMatricula = $errors->any() && old('fecha') !== null;
@endphp

<x-modal name="matricula-create" maxWidth="2xl" :show="$mostrarMatricula" focusable>
    <form
        method="POST"
        action="{{ route('Admin.grupos-asignatura.matriculas.store') }}"
        class="p-6"
        x-data="{
            busqueda: '',
            abierto: false,
            seleccionados: @js($seleccionadosAnteriores),
            alumnos: @js($alumnosSelector),
            coincide(texto) {
                const consulta = String(this.busqueda || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

                return consulta === '' || String(texto || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').includes(consulta);
            },
            elegidos() {
                return this.alumnos.filter((alumno) => this.seleccionados.includes(alumno.id));
            },
            disponibles() {
                return this.alumnos.filter((alumno) => ! this.seleccionados.includes(alumno.id) && this.coincide(alumno.nombre));
            },
            agregar(id) {
                const valor = Number(id);

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
        }"
    >
        @csrf

        <h3 class="text-lg font-semibold text-slate-800">Matrícula del paralelo</h3>
        <p class="mt-1 text-sm text-slate-500">
            Cada alumno tiene un solo paralelo en {{ $periodo->nombre }}. Elige el aula, busca alumnos y guarda.
        </p>

        <div class="mt-4 flex flex-col gap-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-1">
                    <x-input-label for="numero-matricula" value="Número de matrícula" />
                    <x-text-input id="numero-matricula" type="text" class="block w-full bg-slate-50" value="{{ $siguienteNumero }}" readonly />
                    <p class="text-xs text-slate-500">
                        Se genera solo: E + establecimiento + periodo + un correlativo. Si matriculas varios, el contador sigue desde este código y no se repite.
                    </p>
                </div>
                <div class="flex flex-col gap-1">
                    <x-input-label for="fecha" value="Fecha de matrícula" />
                    <x-text-input id="fecha" name="fecha" type="date" class="block w-full" value="{{ $fechaMatricula }}" required />
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <x-input-label for="establecimiento_aula_id" value="Aulas" />
                <select id="establecimiento_aula_id" name="establecimiento_aula_id" class="rounded-lg border-slate-300 text-sm shadow-sm" required>
                    <option value="">Selecciona</option>
                    @foreach ($aulasMatricula as $aulaVista)
                        <option value="{{ $aulaVista['id'] }}" @selected((string) old('establecimiento_aula_id') === (string) $aulaVista['id'])>{{ $aulaVista['etiqueta'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <x-input-label for="busqueda-alumnos-matricula" value="Alumnos" />
                <div class="rounded-lg border border-slate-300 bg-white px-2 py-1 shadow-sm" x-on:click.outside="abierto = false">
                    <div class="flex flex-wrap items-center gap-1">
                        <template x-for="alumno in elegidos()" :key="alumno.id">
                            <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                <span x-text="alumno.nombre"></span>
                                <button type="button" class="text-slate-500 hover:text-slate-800" x-on:click="quitar(alumno.id)" aria-label="Quitar">&times;</button>
                                <input type="hidden" name="alumnos[]" :value="alumno.id">
                            </span>
                        </template>
                        <input
                            id="busqueda-alumnos-matricula"
                            type="search"
                            class="min-w-[12rem] flex-1 border-0 bg-transparent py-1.5 text-sm focus:ring-0"
                            placeholder="Buscar y seleccionar varios"
                            autocomplete="off"
                            x-model="busqueda"
                            x-on:focus="abierto = true"
                            x-on:keydown.enter.prevent="elegirPrimero()"
                        >
                    </div>
                    <div class="max-h-56 overflow-y-auto border-t border-slate-200 py-1" x-show="abierto" x-cloak>
                        <template x-for="alumno in disponibles()" :key="alumno.id">
                            <button type="button" class="block w-full px-2 py-1.5 text-left text-sm text-slate-700 hover:bg-slate-50" x-on:click="agregar(alumno.id)" x-text="alumno.nombre"></button>
                        </template>
                        <p class="px-2 py-2 text-sm text-slate-500" x-show="disponibles().length === 0">No hay alumnos para mostrar.</p>
                    </div>
                </div>
                <p class="text-xs text-slate-500">Puedes marcar varios y filtrar por nombre o apellido en la misma lista.</p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
            <x-primary-button>Guardar</x-primary-button>
        </div>
    </form>
</x-modal>
