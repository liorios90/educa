@php
    use App\Enums\TipoCalificacion;

    $subnivelBag = $errors->getBag('subnivel-'.$subnivel->id);
    $subnivelNombre = $subnivelBag->isNotEmpty() ? old('nombre', $subnivel->nombre) : $subnivel->nombre;
    $subnivelSiglas = $subnivelBag->isNotEmpty() ? old('siglas', $subnivel->siglas) : $subnivel->siglas;
    $subnivelDescripcion = $subnivelBag->isNotEmpty() ? old('descripcion', $subnivel->descripcion) : $subnivel->descripcion;
    $subnivelTipo = $subnivelBag->isNotEmpty()
        ? old('tipo_calificacion', $subnivel->tipo_calificacion?->value)
        : $subnivel->tipo_calificacion?->value;
    $gradosCount = $subnivel->grados_count ?? $subnivel->grados->count();
@endphp

<article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="min-w-0">
            <p class="font-semibold text-slate-800">
                {{ $subnivel->nombre }}
                @if ($subnivel->siglas)
                    <span class="ml-1 font-normal text-slate-500">({{ $subnivel->siglas }})</span>
                @endif
            </p>
            <p class="mt-1 text-sm text-slate-500">{{ $subnivel->descripcion ?: 'Sin descripción' }}</p>
            <p class="mt-1 text-xs text-slate-400">
                {{ $subnivel->tipo_calificacion?->label() ?? 'Sin tipo de calificación' }}
                · {{ $gradosCount }} grados
            </p>
        </div>
        <div class="flex items-center gap-3 sm:shrink-0">
            <button type="button" class="font-medium text-indigo-600 hover:text-indigo-500" x-on:click="$dispatch('open-modal', 'grados-{{ $subnivel->id }}')">
                Grados
            </button>
            <button type="button" class="font-medium text-indigo-600 hover:text-indigo-500" @click="editingSubnivelId = {{ $subnivel->id }}">
                Editar
            </button>
            <form method="POST" action="{{ route('sistemas.estructura.subniveles.destroy', [$nivel, $subnivel]) }}" onsubmit="return confirm('¿Eliminar este subnivel?')">
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
        action="{{ route('sistemas.estructura.subniveles.update', [$nivel, $subnivel]) }}"
        class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50 px-4 py-4 sm:px-6 md:flex-row md:flex-wrap md:items-end"
        x-show="editingSubnivelId === {{ $subnivel->id }}"
        x-cloak
    >
        @csrf
        @method('patch')
        <div class="md:min-w-40 md:flex-1">
            <x-input-label :for="'nombre-subnivel-'.$subnivel->id" value="Nombre" />
            <x-text-input :id="'nombre-subnivel-'.$subnivel->id" class="mt-1 block w-full" type="text" name="nombre" :value="$subnivelNombre" required />
            <x-input-error class="mt-2" :messages="$subnivelBag->get('nombre')" />
        </div>
        <div class="md:w-28">
            <x-input-label :for="'siglas-subnivel-'.$subnivel->id" value="Siglas" />
            <x-text-input :id="'siglas-subnivel-'.$subnivel->id" class="mt-1 block w-full" type="text" name="siglas" :value="$subnivelSiglas" maxlength="10" />
            <x-input-error class="mt-2" :messages="$subnivelBag->get('siglas')" />
        </div>
        <div class="md:min-w-48">
            <x-input-label :for="'tipo-calificacion-subnivel-'.$subnivel->id" value="Tipo de calificación" />
            <select
                id="tipo-calificacion-subnivel-{{ $subnivel->id }}"
                name="tipo_calificacion"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                <option value="">Selecciona un tipo</option>
                @foreach (TipoCalificacion::cases() as $option)
                    <option value="{{ $option->value }}" @selected((string) $subnivelTipo === $option->value)>
                        {{ $option->label() }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$subnivelBag->get('tipo_calificacion')" />
        </div>
        <div class="md:min-w-40 md:flex-1">
            <x-input-label :for="'descripcion-subnivel-'.$subnivel->id" value="Descripción" />
            <x-text-input :id="'descripcion-subnivel-'.$subnivel->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$subnivelDescripcion" />
            <x-input-error class="mt-2" :messages="$subnivelBag->get('descripcion')" />
        </div>
        <div class="flex gap-2">
            <x-primary-button>Guardar</x-primary-button>
            <x-secondary-button type="button" x-on:click="editingSubnivelId = null">Cancelar</x-secondary-button>
        </div>
    </form>
</article>
