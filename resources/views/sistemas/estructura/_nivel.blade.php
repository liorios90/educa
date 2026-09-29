@php
    $nivelBag = $errors->getBag('nivel-'.$nivel->id);
    $nivelNombre = $nivelBag->isNotEmpty() ? old('nombre', $nivel->nombre) : $nivel->nombre;
    $nivelSiglas = $nivelBag->isNotEmpty() ? old('siglas', $nivel->siglas) : $nivel->siglas;
    $nivelDescripcion = $nivelBag->isNotEmpty() ? old('descripcion', $nivel->descripcion) : $nivel->descripcion;
    $subnivelesCount = $nivel->subniveles_count ?? $nivel->subniveles->count();
@endphp

<article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="min-w-0">
            <p class="font-semibold text-slate-800">
                {{ $nivel->nombre }}
                @if ($nivel->siglas)
                    <span class="ml-1 font-normal text-slate-500">({{ $nivel->siglas }})</span>
                @endif
            </p>
            <p class="mt-1 text-sm text-slate-500">{{ $nivel->descripcion ?: 'Sin descripción' }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ $subnivelesCount }} subniveles</p>
        </div>
        <div class="flex items-center gap-3 sm:shrink-0">
            <a href="{{ route('sistemas.estructura.niveles.show', $nivel) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                Subniveles
            </a>
            <button type="button" class="font-medium text-indigo-600 hover:text-indigo-500" @click="editingNivelId = {{ $nivel->id }}">
                Editar
            </button>
            <form method="POST" action="{{ route('sistemas.estructura.niveles.destroy', $nivel) }}" onsubmit="return confirm('¿Eliminar este nivel?')">
                @csrf
                @method('delete')
                <button type="submit" class="font-medium text-red-600 hover:text-red-500">
                    Eliminar
                </button>
            </form>
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('sistemas.estructura.niveles.update', $nivel) }}"
        class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50 px-4 py-4 sm:px-6 md:flex-row md:flex-wrap md:items-end"
        x-show="editingNivelId === {{ $nivel->id }}"
        x-cloak
    >
        @csrf
        @method('patch')
        <div class="md:min-w-48 md:flex-1">
            <x-input-label :for="'nombre-nivel-'.$nivel->id" value="Nombre" />
            <x-text-input :id="'nombre-nivel-'.$nivel->id" class="mt-1 block w-full" type="text" name="nombre" :value="$nivelNombre" required />
            <x-input-error class="mt-2" :messages="$nivelBag->get('nombre')" />
        </div>
        <div class="md:w-28">
            <x-input-label :for="'siglas-nivel-'.$nivel->id" value="Siglas" />
            <x-text-input :id="'siglas-nivel-'.$nivel->id" class="mt-1 block w-full" type="text" name="siglas" :value="$nivelSiglas" maxlength="10" />
            <x-input-error class="mt-2" :messages="$nivelBag->get('siglas')" />
        </div>
        <div class="md:min-w-48 md:flex-1">
            <x-input-label :for="'descripcion-nivel-'.$nivel->id" value="Descripción" />
            <x-text-input :id="'descripcion-nivel-'.$nivel->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$nivelDescripcion" />
            <x-input-error class="mt-2" :messages="$nivelBag->get('descripcion')" />
        </div>
        <div class="flex gap-2">
            <x-primary-button>Guardar</x-primary-button>
            <x-secondary-button type="button" x-on:click="editingNivelId = null">Cancelar</x-secondary-button>
        </div>
    </form>
</article>
