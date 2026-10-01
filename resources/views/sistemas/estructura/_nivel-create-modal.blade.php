@php
    $nivelCreateBag = $errors->getBag('nivel-create');
    $nivelCreateNombre = $nivelCreateBag->isNotEmpty() ? old('nombre') : '';
    $nivelCreateSiglas = $nivelCreateBag->isNotEmpty() ? old('siglas') : '';
    $nivelCreateDescripcion = $nivelCreateBag->isNotEmpty() ? old('descripcion') : '';
@endphp

<x-modal name="nivel-create" maxWidth="2xl" :show="$nivelCreateBag->isNotEmpty()" focusable>
    <form method="POST" action="{{ route('sistemas.estructura.niveles.store') }}" class="p-6">
        @csrf

        <h3 class="text-lg font-semibold text-slate-800">Nuevo nivel</h3>
        <p class="mt-1 text-sm text-slate-500">
            Crea el nivel y luego entra a él para definir sus subniveles y grados.
        </p>

        <div class="mt-4 flex flex-col gap-4">
            <div>
                <x-input-label for="nombre-nivel-create" value="Nombre" />
                <x-text-input id="nombre-nivel-create" class="mt-1 block w-full" type="text" name="nombre" :value="$nivelCreateNombre" required />
                <x-input-error class="mt-2" :messages="$nivelCreateBag->get('nombre')" />
            </div>
            <div>
                <x-input-label for="siglas-nivel-create" value="Siglas" />
                <x-text-input id="siglas-nivel-create" class="mt-1 block w-full" type="text" name="siglas" :value="$nivelCreateSiglas" maxlength="10" />
                <x-input-error class="mt-2" :messages="$nivelCreateBag->get('siglas')" />
            </div>
            <div>
                <x-input-label for="descripcion-nivel-create" value="Descripción" />
                <x-text-input id="descripcion-nivel-create" class="mt-1 block w-full" type="text" name="descripcion" :value="$nivelCreateDescripcion" />
                <x-input-error class="mt-2" :messages="$nivelCreateBag->get('descripcion')" />
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
            <x-primary-button>Crear nivel</x-primary-button>
        </div>
    </form>
</x-modal>
