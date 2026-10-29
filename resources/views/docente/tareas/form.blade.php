@php
    $seleccionados = array_map(
        fn (mixed $id): int => (int) $id,
        (array) old('destinatarios', $tarea?->destinatarios?->pluck('alumno_id')->all() ?? []),
    );
    $tipoActual = old('contenido_tipo', $tarea?->contenido_tipo?->value ?? \App\Enums\TipoContenidoTarea::Texto->value);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('Docente.tareas.index') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">← Tareas</a>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-slate-800">{{ $tarea?->nombre ?? 'Nueva tarea' }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $contexto->etiqueta() }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form
                method="POST"
                action="{{ $tarea ? route('Docente.tareas.update', $tarea) : route('Docente.tareas.store') }}"
                enctype="multipart/form-data"
                class="flex flex-col gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                x-data="{
                    tipo: @js($tipoActual),
                    busqueda: '',
                    abierto: false,
                    todaElAula: @js((bool) old('toda_el_aula', false)),
                    seleccionados: @js($seleccionados),
                    opciones: @js($alumnos),
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
                @csrf
                @if ($tarea)
                    @method('PUT')
                @endif

                <input type="hidden" name="establecimiento_aula_id" value="{{ $contexto->aula->id }}">
                <input type="hidden" name="establecimiento_asignatura_id" value="{{ $contexto->asignatura->id }}">
                <input type="hidden" name="establecimiento_periodo_ciclo_id" value="{{ $contexto->ciclo->id }}">
                <input type="hidden" name="parcial_numero" value="{{ $contexto->parcialNumero }}">

                <div>
                    <x-input-label for="nombre" value="Nombre de la tarea" />
                    <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre', $tarea?->nombre)" required maxlength="160" />
                    <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
                </div>

                <div>
                    <x-input-label for="descripcion" value="Descripción" />
                    <div class="mt-1">
                        <x-editor-formato name="descripcion" :value="old('descripcion', $tarea?->descripcion ?? '')" />
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('descripcion')" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="fecha_inicio" value="Fecha de inicio" />
                        <x-text-input id="fecha_inicio" name="fecha_inicio" type="date" class="mt-1 block w-full" :value="old('fecha_inicio', $tarea?->fecha_inicio?->toDateString())" required />
                        <x-input-error class="mt-2" :messages="$errors->get('fecha_inicio')" />
                    </div>
                    <div>
                        <x-input-label for="fecha_fin" value="Fecha de entrega" />
                        <x-text-input id="fecha_fin" name="fecha_fin" type="date" class="mt-1 block w-full" :value="old('fecha_fin', $tarea?->fecha_fin?->toDateString())" required />
                        <x-input-error class="mt-2" :messages="$errors->get('fecha_fin')" />
                    </div>
                </div>

                <div>
                    <x-input-label value="Alumnos" />
                    <label class="mt-2 flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="toda_el_aula" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" x-model="todaElAula">
                        Toda el aula
                    </label>
                    <div class="mt-3 rounded-lg border border-slate-300 bg-white px-2 py-1 shadow-sm" x-show="! todaElAula" x-on:click="abierto = true" x-on:click.outside="abierto = false">
                        <div class="flex flex-wrap items-center gap-1">
                            <template x-for="opcion in elegidos()" :key="'elegido-'+opcion.id">
                                <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                    <span x-text="opcion.etiqueta"></span>
                                    <button type="button" class="text-slate-500 hover:text-slate-800" x-on:click="quitar(opcion.id)" aria-label="Quitar">&times;</button>
                                    <input type="hidden" name="destinatarios[]" :value="opcion.id">
                                </span>
                            </template>
                            <input
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
                            <template x-for="opcion in disponibles()" :key="opcion.id">
                                <button type="button" class="block w-full px-2 py-1.5 text-left text-sm text-slate-700 hover:bg-slate-50" x-on:click="agregar(opcion.id)" x-text="opcion.etiqueta"></button>
                            </template>
                            <p class="px-2 py-2 text-sm text-slate-500" x-show="disponibles().length === 0">No hay alumnos para mostrar.</p>
                        </div>
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('destinatarios')" />
                </div>

                <div>
                    <x-input-label for="contenido_tipo" value="Contenido de la tarea" />
                    <select id="contenido_tipo" name="contenido_tipo" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" x-model="tipo" required>
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('contenido_tipo')" />
                </div>

                <div x-show="tipo === 'texto'" x-cloak>
                    <x-input-label for="contenido_texto" value="Texto" />
                    <textarea id="contenido_texto" name="contenido_texto" rows="5" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('contenido_texto', $tarea?->contenido_texto) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('contenido_texto')" />
                </div>

                <div x-show="tipo === 'url'" x-cloak>
                    <x-input-label for="contenido_url" value="Enlace" />
                    <x-text-input id="contenido_url" name="contenido_url" type="url" class="mt-1 block w-full" :value="old('contenido_url', $tarea?->contenido_url)" maxlength="500" />
                    <x-input-error class="mt-2" :messages="$errors->get('contenido_url')" />
                </div>

                <div x-show="tipo === 'pdf' || tipo === 'imagen'" x-cloak>
                    <x-input-label for="contenido_archivo" :value="old('contenido_tipo', $tipoActual) === 'imagen' ? 'Imagen' : 'PDF'" />
                    @if ($tarea?->contenido_path)
                        <p class="mt-1 text-sm text-slate-500">Archivo actual: {{ $tarea->contenido_nombre }}. Sube uno nuevo solo si quieres reemplazarlo.</p>
                        <div class="mt-3" x-show="tipo === @js($tarea->contenido_tipo->value)">
                            <x-tarea-archivo-preview :tarea="$tarea" />
                        </div>
                    @endif
                    <input id="contenido_archivo" name="contenido_archivo" type="file" class="mt-3 block w-full text-sm text-slate-700" :accept="tipo === 'pdf' ? '.pdf,application/pdf' : 'image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp'">
                    <x-input-error class="mt-2" :messages="$errors->get('contenido_archivo')" />
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('Docente.tareas.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</a>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                        {{ $tarea ? 'Guardar cambios' : 'Enviar tarea' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
