<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Calificar {{ $tipo->labelPlural() }}</h2>
            <a class="mt-2 inline-flex text-sm font-medium text-indigo-700 hover:text-indigo-900" href="{{ route('Docente.mejora-calificaciones.index') }}">Mejoras de calificaciones</a>
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
                <x-docente-contexto :aulas="$aulas" :seleccion="$seleccion" :store-route="route($tipo->route('store'))" destino="crear" :requiere-parcial="false" :requiere-ciclo="$tipo->requiereCiclo()">
                    <div class="mt-4 rounded-lg bg-white p-6 shadow-sm" x-show="seleccionCompleta">
                        @if ($contexto && ! $cicloTieneCierre)
                            <p class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                {{ $tipo->mensajeSinConfiguracion() }}
                            </p>
                        @else
                            <form method="POST" action="{{ route($tipo->route('actividades.store')) }}" class="grid grid-cols-1 gap-8 md:grid-cols-2">
                                @csrf
                                <input type="hidden" name="establecimiento_aula_id" :value="aulaId">
                                <input type="hidden" name="establecimiento_asignatura_id" :value="asignaturaId">
                                <input type="hidden" name="establecimiento_periodo_ciclo_id" :value="cicloId">

                                <div class="md:col-span-2">
                                    <x-input-label for="nombre" :value="'Nombre del '.$tipo->label()" />
                                    <x-text-input id="nombre" name="nombre" type="text" maxlength="255" required class="mt-1 block w-full" :value="old('nombre', $actividad?->nombre)" />
                                    <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
                                </div>

                                <div class="md:col-span-2" x-show="coincideConGuardado">
                                    @if ($contexto)
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
