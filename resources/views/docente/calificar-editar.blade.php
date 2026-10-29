<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Editar insumos</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('error'))
                <p class="mb-4 text-sm font-medium text-red-700">{{ session('error') }}</p>
            @endif
            @if (session('status') === 'calificaciones-grabadas')
                <p class="mb-4 text-sm font-medium text-green-700">Calificaciones grabadas correctamente.</p>
            @endif

            @if ($aulas === [])
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    No tienes aulas asignadas en un periodo activo.
                </p>
            @else
                <x-docente-contexto :aulas="$aulas" :seleccion="$seleccion" :store-route="route('Docente.calificar.store')" destino="editar">
                    <div class="mt-4 rounded-lg bg-white p-6 shadow-sm" x-show="seleccionCompleta">
                        <div class="md:col-span-2" x-show="coincideConGuardado">
                            @if ($insumosIngresados === [])
                                <p class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                                    No hay insumos grabados en este aula, asignatura y parcial.
                                </p>
                            @else
                                <div
                                    class="relative"
                                    x-data="{
                                        abierto: false,
                                        busqueda: '',
                                        actividadId: '{{ $actividadEditando?->id ?? '' }}',
                                        grupos: @js($insumosIngresados),
                                        indexUrl: @js(route('Docente.calificar.editar')),
                                        get filtrados() {
                                            const consulta = this.busqueda.trim().toLowerCase();
                                            if (! consulta) {
                                                return this.grupos;
                                            }

                                            return this.grupos
                                                .map((grupo) => {
                                                    if (grupo.etiqueta.toLowerCase().includes(consulta)) {
                                                        return grupo;
                                                    }

                                                    const actividades = grupo.actividades.filter((actividad) => actividad.nombre.toLowerCase().includes(consulta));

                                                    return actividades.length > 0
                                                        ? Object.assign({}, grupo, { actividades })
                                                        : null;
                                                })
                                                .filter(Boolean);
                                        },
                                        get etiqueta() {
                                            if (! this.actividadId) {
                                                return 'Seleccionar...';
                                            }

                                            for (const grupo of this.grupos) {
                                                const actividad = grupo.actividades.find((item) => String(item.id) === String(this.actividadId));
                                                if (actividad) {
                                                    return actividad.nombre;
                                                }
                                            }

                                            return 'Seleccionar...';
                                        },
                                        elegir(id) {
                                            this.abierto = false;
                                            if (String(id ?? '') === String(this.actividadId)) {
                                                return;
                                            }

                                            const url = new URL(this.indexUrl, window.location.origin);
                                            if (id) {
                                                url.searchParams.set('actividad', id);
                                            } else {
                                                url.searchParams.delete('actividad');
                                            }

                                            window.location.assign(url.toString());
                                        },
                                    }"
                                    @click.outside="abierto = false"
                                >
                                    <x-input-label for="insumo-ingresado" value="Seleccione un insumo" />
                                    <button
                                        id="insumo-ingresado"
                                        type="button"
                                        class="mt-1 flex w-full items-center justify-between rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                        @click="abierto = ! abierto; if (abierto) { $nextTick(() => $refs.busquedaInsumo.focus()) }"
                                    >
                                        <span x-text="etiqueta">Seleccionar...</span>
                                        <span class="text-slate-400">▾</span>
                                    </button>
                                    <div
                                        x-show="abierto"
                                        x-cloak
                                        class="absolute z-20 mt-1 w-full overflow-hidden rounded-md border border-slate-200 bg-white shadow-lg"
                                    >
                                        <div class="border-b border-slate-200 p-2">
                                            <input
                                                x-ref="busquedaInsumo"
                                                type="search"
                                                x-model="busqueda"
                                                placeholder="Buscar..."
                                                class="block w-full rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                        </div>
                                        <ul class="max-h-64 overflow-y-auto py-1 text-sm" role="listbox">
                                            <li>
                                                <button
                                                    type="button"
                                                    class="block w-full px-3 py-2 text-left"
                                                    :class="! actividadId ? 'bg-blue-500 text-white' : 'hover:bg-slate-100'"
                                                    @click="elegir('')"
                                                >Seleccionar...</button>
                                            </li>
                                            <template x-for="grupo in filtrados" :key="grupo.id">
                                                <li>
                                                    <p class="px-3 py-1.5 font-semibold text-slate-800" x-text="grupo.etiqueta"></p>
                                                    <ul>
                                                        <template x-for="actividad in grupo.actividades" :key="actividad.id">
                                                            <li>
                                                                <button
                                                                    type="button"
                                                                    class="block w-full px-6 py-1.5 text-left"
                                                                    :class="String(actividadId) === String(actividad.id) ? 'bg-blue-500 text-white' : 'text-slate-700 hover:bg-slate-100'"
                                                                    x-text="actividad.nombre"
                                                                    @click="elegir(actividad.id)"
                                                                ></button>
                                                            </li>
                                                        </template>
                                                    </ul>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if ($actividadEditando)
                            <form
                                method="POST"
                                action="{{ route('Docente.calificar.actividades.update', $actividadEditando) }}"
                                class="mt-8 grid grid-cols-1 gap-8 md:grid-cols-2"
                                x-show="coincideConGuardado"
                            >
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="establecimiento_aula_id" :value="aulaId">
                                <input type="hidden" name="establecimiento_asignatura_id" :value="asignaturaId">
                                <input type="hidden" name="establecimiento_periodo_ciclo_id" :value="cicloId">
                                <input type="hidden" name="parcial_numero" :value="parcialId">

                                <div>
                                    <x-input-label for="nombre" value="Nombre de la actividad" />
                                    <x-text-input id="nombre" name="nombre" type="text" maxlength="255" required class="mt-1 block w-full" :value="old('nombre', $actividadEditando->nombre)" />
                                    <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
                                </div>

                                <div>
                                    <x-input-label value="Tipo de insumo" />
                                    <p class="mt-1 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">{{ $actividadEditando->detalle?->nombre }}</p>
                                </div>

                                <div class="md:col-span-2">
                                    @if ($contexto)
                                        <p class="mb-3 text-sm text-slate-600">También aparecen los alumnos nuevos y los que aún no tienen nota, para que puedas asignarles un valor.</p>
                                        @include('docente.calificar._alumnos')
                                    @endif
                                </div>

                                <div class="md:col-span-2 flex justify-end">
                                    <x-primary-button>Grabar calificaciones</x-primary-button>
                                </div>
                            </form>
                        @endif
                    </div>
                </x-docente-contexto>
            @endif
        </div>
    </div>
</x-app-layout>
