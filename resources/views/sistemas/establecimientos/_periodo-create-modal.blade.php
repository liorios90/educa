@php
    $createBag = $errors->getBag('periodo-create-'.$oferta->id);
    $createNombre = $createBag->isNotEmpty() ? old('nombre') : '';
    $createInicio = $createBag->isNotEmpty() ? old('fecha_inicio') : '';
    $createFin = $createBag->isNotEmpty() ? old('fecha_fin') : '';
    $createActivo = $createBag->isNotEmpty() ? old('activo') : true;
@endphp

<x-modal name="periodo-create-{{ $oferta->id }}" maxWidth="2xl" :show="$createBag->isNotEmpty()" focusable>
    <form method="POST" action="{{ route('sistemas.establecimientos.periodos.store', $establecimiento) }}" class="p-6">
        @csrf
        <input type="hidden" name="establecimiento_modalidad_jornada_id" value="{{ $oferta->id }}">

        <h3 class="text-lg font-semibold text-slate-800">Nuevo periodo</h3>
        <p class="mt-1 text-sm text-slate-500">
            {{ $oferta->etiqueta() }}. El nombre es el que verán los usuarios. Solo puede haber un periodo activo en esta oferta.
        </p>

        <div class="mt-4 flex flex-col gap-4">
            <div>
                <x-input-label :for="'nombre-periodo-create-'.$oferta->id" value="Nombre del periodo" />
                <x-text-input :id="'nombre-periodo-create-'.$oferta->id" class="mt-1 block w-full" type="text" name="nombre" :value="$createNombre" required />
                <x-input-error class="mt-2" :messages="$createBag->get('nombre')" />
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
            <x-primary-button>Crear periodo</x-primary-button>
        </div>
    </form>
</x-modal>
