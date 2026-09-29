<x-app-layout>
    @php
        $editingPeriodoId = null;

        foreach ($ofertas as $oferta) {
            foreach ($oferta->periodos as $periodo) {
                if ($errors->getBag('periodo-'.$periodo->id)->isNotEmpty()) {
                    $editingPeriodoId = $periodo->id;
                    $openOfertaId = $oferta->id;
                }
            }

            if ($errors->getBag('periodo-create-'.$oferta->id)->isNotEmpty()) {
                $openOfertaId = $oferta->id;
            }
        }
    @endphp

    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Periodos
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $establecimiento->nombre }}
            </p>
        </div>
    </x-slot>

    <div
        class="py-8"
        x-data="{
            activeOfertaId: @js($openOfertaId),
            editingPeriodoId: @js($editingPeriodoId),
        }"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'periodo-created')
                <p class="mb-4 text-sm font-medium text-green-700">Periodo activado correctamente.</p>
            @endif
            @if (session('status') === 'periodo-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Periodo actualizado correctamente.</p>
            @endif
            @if (session('status') === 'periodo-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Periodo eliminado correctamente.</p>
            @endif

            <p class="mb-4 text-sm text-slate-500">
                <a href="{{ route('sistemas.establecimientos') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Establecimientos</a>
                <span class="text-slate-400"> / </span>
                {{ $establecimiento->nombre }}
            </p>

            @if ($ofertas->isEmpty())
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    Este establecimiento no tiene modalidades y jornadas.
                    Configúralas al
                    <a href="{{ route('sistemas.establecimientos.edit', $establecimiento) }}" class="font-medium text-indigo-600 hover:text-indigo-500">editar el establecimiento</a>
                    para poder activar periodos.
                </p>
            @else
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" x-show="! editingPeriodoId">
                    <nav class="flex flex-wrap gap-1 border-b border-slate-200 bg-slate-50 px-3 pt-3" role="tablist" aria-label="Ofertas del establecimiento">
                        @foreach ($ofertas as $oferta)
                            <button
                                type="button"
                                role="tab"
                                class="rounded-t-lg px-4 py-2 text-sm font-medium"
                                :class="activeOfertaId === {{ $oferta->id }} ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                                :aria-selected="activeOfertaId === {{ $oferta->id }}"
                                @click="activeOfertaId = {{ $oferta->id }}"
                            >
                                {{ $oferta->etiqueta() }}
                            </button>
                        @endforeach
                    </nav>

                    @foreach ($ofertas as $oferta)
                        @php
                            $createBag = $errors->getBag('periodo-create-'.$oferta->id);
                            $createNombre = $createBag->isNotEmpty() ? old('nombre') : '';
                            $createInicio = $createBag->isNotEmpty() ? old('fecha_inicio') : '';
                            $createFin = $createBag->isNotEmpty() ? old('fecha_fin') : '';
                            $createActivo = $createBag->isNotEmpty() ? old('activo') : true;
                        @endphp
                        <div
                            role="tabpanel"
                            class="p-6"
                            x-show="activeOfertaId === {{ $oferta->id }}"
                            x-cloak
                        >
                            <section class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <h3 class="text-base font-semibold text-slate-800">Nuevo periodo</h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    El nombre es el que verán los usuarios. Solo puede haber un periodo activo en esta oferta.
                                </p>
                                <form method="POST" action="{{ route('sistemas.establecimientos.periodos.store', $establecimiento) }}" class="mt-4 flex flex-col gap-4">
                                    @csrf
                                    <input type="hidden" name="establecimiento_modalidad_jornada_id" value="{{ $oferta->id }}">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div>
                                            <x-input-label :for="'nombre-periodo-create-'.$oferta->id" value="Nombre del periodo" />
                                            <x-text-input :id="'nombre-periodo-create-'.$oferta->id" class="mt-1 block w-full" type="text" name="nombre" :value="$createNombre" required />
                                            <x-input-error class="mt-2" :messages="$createBag->get('nombre')" />
                                        </div>
                                        <div>
                                            <x-input-label :for="'fecha-inicio-periodo-create-'.$oferta->id" value="Fecha de inicio" />
                                            <x-text-input :id="'fecha-inicio-periodo-create-'.$oferta->id" class="mt-1 block w-full" type="date" name="fecha_inicio" :value="$createInicio" required />
                                            <x-input-error class="mt-2" :messages="$createBag->get('fecha_inicio')" />
                                        </div>
                                        <div>
                                            <x-input-label :for="'fecha-fin-periodo-create-'.$oferta->id" value="Fecha de fin" />
                                            <x-text-input :id="'fecha-fin-periodo-create-'.$oferta->id" class="mt-1 block w-full" type="date" name="fecha_fin" :value="$createFin" required />
                                            <x-input-error class="mt-2" :messages="$createBag->get('fecha_fin')" />
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center justify-between gap-4">
                                        <label class="flex items-center gap-2 text-sm text-slate-700">
                                            <input type="hidden" name="activo" value="0">
                                            <input
                                                id="activo-periodo-create-{{ $oferta->id }}"
                                                type="checkbox"
                                                name="activo"
                                                value="1"
                                                class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500"
                                                @checked($createActivo)
                                            >
                                            Activo
                                        </label>
                                        <x-primary-button>Crear periodo</x-primary-button>
                                    </div>
                                </form>
                            </section>

                            <div class="overflow-hidden rounded-xl border border-slate-200">
                                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                                    <thead class="bg-slate-50 text-slate-600">
                                        <tr>
                                            <th class="px-4 py-3 font-medium">Periodo</th>
                                            <th class="px-4 py-3 font-medium">Inicio</th>
                                            <th class="px-4 py-3 font-medium">Fin</th>
                                            <th class="px-4 py-3 font-medium">Estado</th>
                                            <th class="px-4 py-3 font-medium">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-800">
                                        @forelse ($oferta->periodos as $periodo)
                                            <tr @class([
                                                'bg-amber-50' => $periodo->activo,
                                            ])>
                                                <td class="px-4 py-3 font-medium">{{ $periodo->nombre }}</td>
                                                <td class="px-4 py-3 text-slate-500">{{ $periodo->fecha_inicio->format('d/m/Y') }}</td>
                                                <td class="px-4 py-3 text-slate-500">{{ $periodo->fecha_fin->format('d/m/Y') }}</td>
                                                <td class="px-4 py-3">
                                                    @if ($periodo->activo)
                                                        <span class="inline-flex rounded-full bg-amber-200 px-2.5 py-0.5 text-xs font-semibold text-amber-900">Activo</span>
                                                    @else
                                                        <span class="text-slate-400">Inactivo</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-3">
                                                        <button type="button" class="font-medium text-indigo-600 hover:text-indigo-500" @click="editingPeriodoId = {{ $periodo->id }}">
                                                            Editar
                                                        </button>
                                                        <form method="POST" action="{{ route('sistemas.establecimientos.periodos.destroy', [$establecimiento, $periodo]) }}" onsubmit="return confirm('¿Eliminar este periodo?')">
                                                            @csrf
                                                            @method('delete')
                                                            <button type="submit" class="font-medium text-red-600 hover:text-red-500">
                                                                Eliminar
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">
                                                    Esta oferta aún no tiene periodos.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>

                @foreach ($ofertas as $oferta)
                    @foreach ($oferta->periodos as $periodo)
                        @php
                            $periodoBag = $errors->getBag('periodo-'.$periodo->id);
                            $periodoNombre = $periodoBag->isNotEmpty() ? old('nombre', $periodo->nombre) : $periodo->nombre;
                            $periodoInicio = $periodoBag->isNotEmpty() ? old('fecha_inicio', $periodo->fecha_inicio->format('Y-m-d')) : $periodo->fecha_inicio->format('Y-m-d');
                            $periodoFin = $periodoBag->isNotEmpty() ? old('fecha_fin', $periodo->fecha_fin->format('Y-m-d')) : $periodo->fecha_fin->format('Y-m-d');
                            $periodoActivo = $periodoBag->isNotEmpty() ? old('activo', $periodo->activo) : $periodo->activo;
                        @endphp
                        <section
                            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                            x-show="editingPeriodoId === {{ $periodo->id }}"
                            x-cloak
                        >
                            <h3 class="text-base font-semibold text-slate-800">Editar periodo</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $oferta->etiqueta() }}
                            </p>
                            <form method="POST" action="{{ route('sistemas.establecimientos.periodos.update', [$establecimiento, $periodo]) }}" class="mt-4 flex flex-col gap-4">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="establecimiento_modalidad_jornada_id" value="{{ $oferta->id }}">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div>
                                        <x-input-label :for="'nombre-periodo-'.$periodo->id" value="Nombre del periodo" />
                                        <x-text-input :id="'nombre-periodo-'.$periodo->id" class="mt-1 block w-full" type="text" name="nombre" :value="$periodoNombre" required />
                                        <x-input-error class="mt-2" :messages="$periodoBag->get('nombre')" />
                                    </div>
                                    <div>
                                        <x-input-label :for="'fecha-inicio-periodo-'.$periodo->id" value="Fecha de inicio" />
                                        <x-text-input :id="'fecha-inicio-periodo-'.$periodo->id" class="mt-1 block w-full" type="date" name="fecha_inicio" :value="$periodoInicio" required />
                                        <x-input-error class="mt-2" :messages="$periodoBag->get('fecha_inicio')" />
                                    </div>
                                    <div>
                                        <x-input-label :for="'fecha-fin-periodo-'.$periodo->id" value="Fecha de fin" />
                                        <x-text-input :id="'fecha-fin-periodo-'.$periodo->id" class="mt-1 block w-full" type="date" name="fecha_fin" :value="$periodoFin" required />
                                        <x-input-error class="mt-2" :messages="$periodoBag->get('fecha_fin')" />
                                    </div>
                                </div>
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="hidden" name="activo" value="0">
                                    <input
                                        id="activo-periodo-{{ $periodo->id }}"
                                        type="checkbox"
                                        name="activo"
                                        value="1"
                                        class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500"
                                        @checked($periodoActivo)
                                    >
                                    Activo
                                </label>
                                <div class="flex gap-2">
                                    <x-primary-button>Guardar</x-primary-button>
                                    <x-secondary-button type="button" x-on:click="editingPeriodoId = null">Cancelar</x-secondary-button>
                                </div>
                            </form>
                        </section>
                    @endforeach
                @endforeach
            @endif

            <div class="mt-6" x-show="! editingPeriodoId">
                <a href="{{ route('sistemas.establecimientos') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Volver
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
