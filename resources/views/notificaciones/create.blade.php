<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('notificaciones.index') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">← Bandeja</a>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-slate-800">Redactar notificación</h2>
            <p class="mt-1 text-sm text-slate-500">Solo puedes escribir a las personas permitidas para tu rol.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @if ($destinatarios->isEmpty())
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    No hay destinatarios disponibles para tu rol.
                </p>
            @else
                <form method="POST" action="{{ route('notificaciones.store') }}" class="flex flex-col gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    @csrf

                    @php
                        $opcionesDestinatarios = $destinatarios
                            ->map(fn (array $destinatario): array => [
                                'id' => (int) $destinatario['id'],
                                'nombre' => $destinatario['nombre'],
                                'rol' => $destinatario['rol'],
                                'etiqueta' => $destinatario['nombre'].' ('.$destinatario['rol'].')',
                            ])
                            ->values()
                            ->all();
                        $destinatariosSeleccionados = array_map(
                            fn (mixed $id): int => (int) $id,
                            (array) old('destinatarios', []),
                        );
                    @endphp

                    <div
                        class="flex flex-col gap-1"
                        x-data="{
                            busqueda: '',
                            abierto: false,
                            seleccionados: @js($destinatariosSeleccionados),
                            opciones: @js($opcionesDestinatarios),
                            coincide(texto) {
                                const consulta = String(this.busqueda || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

                                return consulta === '' || String(texto || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').includes(consulta);
                            },
                            elegidos() {
                                return this.opciones.filter((opcion) => this.seleccionados.includes(opcion.id));
                            },
                            disponibles() {
                                return this.opciones.filter((opcion) => ! this.seleccionados.includes(opcion.id) && this.coincide(opcion.etiqueta));
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
                        <x-input-label for="busqueda-destinatarios" value="Destinatarios" />
                        <div class="rounded-lg border border-slate-300 bg-white px-2 py-1 shadow-sm" x-on:click="abierto = true" x-on:click.outside="abierto = false">
                            <div class="flex flex-wrap items-center gap-1">
                                <template x-for="opcion in elegidos()" :key="'elegido-'+opcion.id">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs text-slate-700">
                                        <span>
                                            <span class="font-semibold text-slate-800" x-text="opcion.nombre"></span>
                                            <span class="font-normal text-slate-500" x-text="' (' + opcion.rol + ')'"></span>
                                        </span>
                                        <button type="button" class="text-slate-500 hover:text-slate-800" x-on:click="quitar(opcion.id)" aria-label="Quitar">&times;</button>
                                        <input type="hidden" name="destinatarios[]" :value="opcion.id">
                                    </span>
                                </template>
                                <input
                                    id="busqueda-destinatarios"
                                    type="search"
                                    class="min-w-[12rem] flex-1 cursor-pointer border-0 bg-transparent py-1.5 text-sm focus:ring-0"
                                    placeholder="Buscar y seleccionar varios"
                                    autocomplete="off"
                                    x-model="busqueda"
                                    x-on:focus="abierto = true"
                                    x-on:keydown.enter.prevent="elegirPrimero()"
                                    x-on:keydown.escape.stop="abierto = false"
                                >
                            </div>
                            <div
                                class="max-h-56 overflow-y-auto border-t border-slate-200 py-1"
                                role="listbox"
                                aria-multiselectable="true"
                                aria-label="Destinatarios"
                                x-show="abierto"
                                x-cloak
                            >
                                <template x-for="opcion in disponibles()" :key="opcion.id">
                                    <button
                                        type="button"
                                        class="block w-full px-2 py-1.5 text-left text-sm hover:bg-slate-50"
                                        role="option"
                                        x-on:click="agregar(opcion.id)"
                                    >
                                        <span class="font-semibold text-slate-800" x-text="opcion.nombre"></span>
                                        <span class="font-normal text-slate-500" x-text="' (' + opcion.rol + ')'"></span>
                                    </button>
                                </template>
                                <p class="px-2 py-2 text-sm text-slate-500" x-show="disponibles().length === 0">No hay destinatarios para mostrar.</p>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500">Puedes marcar varios y filtrar por nombre, rol, aula o alumno en la misma lista.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('destinatarios')" />
                    </div>

                    <div>
                        <x-input-label for="asunto" value="Asunto" />
                        <x-text-input id="asunto" name="asunto" type="text" class="mt-1 block w-full" :value="old('asunto')" required maxlength="160" />
                        <x-input-error class="mt-2" :messages="$errors->get('asunto')" />
                    </div>

                    <div>
                        <x-input-label for="cuerpo" value="Mensaje" />
                        <textarea
                            id="cuerpo"
                            name="cuerpo"
                            rows="8"
                            required
                            maxlength="5000"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >{{ old('cuerpo') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('cuerpo')" />
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                            Enviar
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
