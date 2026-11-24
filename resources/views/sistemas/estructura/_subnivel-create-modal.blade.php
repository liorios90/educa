@php
    $subnivelCreateBag = $errors->getBag('subnivel-create-'.$nivel->id);
    $subnivelCreateNombre = $subnivelCreateBag->isNotEmpty() ? old('nombre') : '';
    $subnivelCreateSiglas = $subnivelCreateBag->isNotEmpty() ? old('siglas') : '';
    $subnivelCreateDescripcion = $subnivelCreateBag->isNotEmpty() ? old('descripcion') : '';
    $subnivelCreateOrganizacion = $subnivelCreateBag->isNotEmpty() ? old('organizacion', 'areas') : 'areas';
@endphp

<x-modal name="subnivel-create-{{ $nivel->id }}" maxWidth="2xl" :show="$subnivelCreateBag->isNotEmpty()" focusable>
    <form method="POST" action="{{ route('sistemas.estructura.subniveles.store', $nivel) }}" class="p-6">
        @csrf

        <h3 class="text-lg font-semibold text-slate-800">Nuevo subnivel</h3>
        <p class="mt-1 text-sm text-slate-500">
            {{ $nivel->nombre }}
        </p>

        <div class="mt-4 flex flex-col gap-4">
            <div>
                <x-input-label :for="'nombre-subnivel-create-'.$nivel->id" value="Nombre" />
                <x-text-input :id="'nombre-subnivel-create-'.$nivel->id" class="mt-1 block w-full" type="text" name="nombre" :value="$subnivelCreateNombre" required />
                <x-input-error class="mt-2" :messages="$subnivelCreateBag->get('nombre')" />
            </div>
            <div>
                <x-input-label :for="'siglas-subnivel-create-'.$nivel->id" value="Siglas" />
                <x-text-input :id="'siglas-subnivel-create-'.$nivel->id" class="mt-1 block w-full" type="text" name="siglas" :value="$subnivelCreateSiglas" maxlength="10" />
                <x-input-error class="mt-2" :messages="$subnivelCreateBag->get('siglas')" />
            </div>
            <div>
                <x-input-label :for="'organizacion-subnivel-create-'.$nivel->id" value="Se organiza por" />
                <select
                    id="organizacion-subnivel-create-{{ $nivel->id }}"
                    name="organizacion"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option value="areas" @selected($subnivelCreateOrganizacion === 'areas')>Áreas</option>
                    <option value="ambitos" @selected($subnivelCreateOrganizacion === 'ambitos')>Ámbitos</option>
                </select>
                <x-input-error class="mt-2" :messages="$subnivelCreateBag->get('organizacion')" />
            </div>
            <div>
                <x-input-label :for="'descripcion-subnivel-create-'.$nivel->id" value="Descripción" />
                <x-text-input :id="'descripcion-subnivel-create-'.$nivel->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$subnivelCreateDescripcion" />
                <x-input-error class="mt-2" :messages="$subnivelCreateBag->get('descripcion')" />
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
            <x-primary-button>Crear subnivel</x-primary-button>
        </div>
    </form>
</x-modal>
