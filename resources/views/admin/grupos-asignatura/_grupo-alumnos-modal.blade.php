<x-modal name="grupo-alumnos" maxWidth="2xl">
    <div
        class="p-6"
        x-data="{
            origen: '',
            gradoId: null,
            titulo: '',
            seleccionados: [],
            ocupados: {},
            aulas: @js($aulasMatricula),
            busqueda: '',
            abierto: false,
            coincide(texto) {
                const consulta = String(this.busqueda || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

                return consulta === '' || String(texto || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').includes(consulta);
            },
            ocupadoPor(alumnoId) {
                const dato = this.ocupados[String(alumnoId)];

                if (! dato) {
                    return null;
                }

                if (Number(dato.grupoId) === Number(this.origen)) {
                    return null;
                }

                return dato.nombre;
            },
            alumnosDelGrado() {
                if (! this.gradoId) {
                    return [];
                }

                return this.aulas
                    .filter((aula) => Number(aula.gradoId) === Number(this.gradoId))
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

                if (this.ocupadoPor(valor) !== null) {
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
            confirmar() {
                this.$dispatch('grupo-alumnos-listos', {
                    origen: this.origen,
                    seleccionados: this.seleccionados,
                });

                this.$nextTick(() => document.getElementById('grupo-alumnos-' + this.origen)?.submit());
            },
        }"
        x-on:abrir-grupo-alumnos.window="
            origen = $event.detail.origen;
            gradoId = $event.detail.gradoId;
            titulo = $event.detail.titulo;
            seleccionados = ($event.detail.seleccionados || []).map(Number);
            ocupados = $event.detail.ocupados || {};
            busqueda = '';
            abierto = false;
            $dispatch('open-modal', 'grupo-alumnos');
        "
    >
        <div class="flex flex-col gap-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-800">Alumnos del grupo</h3>
                <p class="mt-1 text-sm text-slate-500" x-text="titulo || 'Busca y selecciona varios alumnos.'"></p>
            </div>

            <div class="flex flex-col gap-1">
                <x-input-label for="busqueda-alumnos-grupo-existente" value="Alumnos" />
                <div class="rounded-lg border border-slate-300 bg-white px-2 py-1 shadow-sm" x-on:click.outside="abierto = false">
                    <div class="flex flex-wrap items-center gap-1">
                        <template x-for="alumno in elegidos()" :key="alumno.id">
                            <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                <span x-text="alumno.nombre"></span>
                                <button type="button" class="text-slate-500 hover:text-slate-800" x-on:click="quitar(alumno.id)" aria-label="Quitar">&times;</button>
                            </span>
                        </template>
                        <input
                            id="busqueda-alumnos-grupo-existente"
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

            <div class="flex justify-end gap-2">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button type="button" x-on:click="confirmar()">Guardar alumnos</x-primary-button>
            </div>
        </div>
    </div>
</x-modal>
