<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Calificar Nuevos insumos</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'calificaciones-grabadas')
                <p class="mb-4 text-sm font-medium text-green-700">Calificaciones grabadas correctamente.</p>
            @endif

            @if ($aulas === [])
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    No tienes aulas asignadas en un periodo activo.
                </p>
            @else
                <x-docente-contexto :aulas="$aulas" :seleccion="$seleccion" :store-route="route('Docente.calificar.store')" destino="crear">
                    <div class="mt-4 rounded-lg bg-white p-6 shadow-sm" x-show="seleccionCompleta">
                        <form method="POST" action="{{ route('Docente.calificar.actividades.store') }}" class="grid grid-cols-1 gap-8 md:grid-cols-2">
                            @csrf
                            <input type="hidden" name="establecimiento_aula_id" :value="aulaId">
                            <input type="hidden" name="establecimiento_asignatura_id" :value="asignaturaId">
                            <input type="hidden" name="establecimiento_periodo_ciclo_id" :value="cicloId">
                            <input type="hidden" name="parcial_numero" :value="parcialId">

                            <div>
                                <x-input-label for="nombre" value="Nombre de la actividad" />
                                <x-text-input id="nombre" name="nombre" type="text" maxlength="255" required class="mt-1 block w-full" :value="old('nombre')" />
                                <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
                            </div>

                            <div>
                                <x-input-label for="tipo_insumo_detalle_id" value="Elegir un tipo de insumo:" />
                                <select
                                    id="tipo_insumo_detalle_id"
                                    name="tipo_insumo_detalle_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Selecciona un tipo de insumo</option>
                                    <template x-for="insumo in insumos" :key="insumo.id">
                                        <option :value="insumo.id" x-text="insumo.etiqueta"></option>
                                    </template>
                                </select>
                                <p class="mt-2 text-sm text-slate-500" x-show="seleccionCompleta && insumos.length === 0">
                                    Esta materia no tiene un tipo de insumo asignado en el aula.
                                </p>
                                <x-input-error class="mt-2" :messages="$errors->get('tipo_insumo_detalle_id')" />
                            </div>

                            <div class="md:col-span-2" x-show="coincideConGuardado">
                                @if ($contexto)
                                    @include('docente.calificar._alumnos')
                                @endif
                            </div>

                            <div class="md:col-span-2 flex justify-end">
                                <x-primary-button x-bind:disabled="insumos.length === 0">Grabar calificaciones</x-primary-button>
                            </div>
                        </form>
                    </div>
                </x-docente-contexto>
            @endif
        </div>
    </div>
</x-app-layout>
