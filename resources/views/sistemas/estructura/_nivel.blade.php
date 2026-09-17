@php
    use App\Enums\TipoCalificacion;

    $nivelBag = $errors->getBag('nivel-'.$nivel->id);
    $subnivelCreateBag = $errors->getBag('subnivel-create-'.$nivel->id);
    $nivelNombre = $nivelBag->isNotEmpty() ? old('nombre', $nivel->nombre) : $nivel->nombre;
    $nivelSiglas = $nivelBag->isNotEmpty() ? old('siglas', $nivel->siglas) : $nivel->siglas;
    $nivelDescripcion = $nivelBag->isNotEmpty() ? old('descripcion', $nivel->descripcion) : $nivel->descripcion;
    $subnivelCreateNombre = $subnivelCreateBag->isNotEmpty() ? old('nombre') : '';
    $subnivelCreateSiglas = $subnivelCreateBag->isNotEmpty() ? old('siglas') : '';
    $subnivelCreateDescripcion = $subnivelCreateBag->isNotEmpty() ? old('descripcion') : '';
    $subnivelCreateTipo = $subnivelCreateBag->isNotEmpty()
        ? old('tipo_calificacion')
        : TipoCalificacion::Calificacion->value;
@endphp

<article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <button
            type="button"
            class="flex min-w-0 flex-1 items-start gap-3 text-left"
            @click="toggleNivel({{ $nivel->id }})"
        >
            <span
                class="mt-1 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition"
                :class="{ 'rotate-180 bg-indigo-100 text-indigo-700': openNivelId === {{ $nivel->id }} }"
            >
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </span>
            <span class="min-w-0">
                <span class="block font-semibold text-slate-800">
                    {{ $nivel->nombre }}
                    @if ($nivel->siglas)
                        <span class="ml-1 font-normal text-slate-500">({{ $nivel->siglas }})</span>
                    @endif
                </span>
                <span class="mt-1 block text-sm text-slate-500">{{ $nivel->descripcion ?: 'Sin descripción' }}</span>
                <span class="mt-1 block text-xs text-slate-400">{{ $nivel->subniveles->count() }} subniveles</span>
            </span>
        </button>
        <div class="flex items-center gap-3 sm:shrink-0">
            <button type="button" class="font-medium text-indigo-600 hover:text-indigo-500" @click.stop="editNivel({{ $nivel->id }})">
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

    <div class="space-y-4 border-t border-slate-100 bg-slate-50 px-4 py-4 sm:px-6" x-cloak x-show="openNivelId === {{ $nivel->id }}">
        <form
            method="POST"
            action="{{ route('sistemas.estructura.niveles.update', $nivel) }}"
            class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 md:flex-row md:flex-wrap md:items-end"
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

        <div class="flex flex-col gap-3">
            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Subniveles</h4>

            @forelse ($nivel->subniveles as $subnivel)
                @include('sistemas.estructura._subnivel', ['nivel' => $nivel, 'subnivel' => $subnivel])
            @empty
                <p class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-3 text-sm text-slate-500">
                    Este nivel aún no tiene subniveles.
                </p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('sistemas.estructura.subniveles.store', $nivel) }}" class="flex flex-col gap-4 rounded-xl border border-indigo-100 bg-white p-4 md:flex-row md:flex-wrap md:items-end">
            @csrf
            <div class="md:min-w-40 md:flex-1">
                <x-input-label :for="'nombre-subnivel-create-'.$nivel->id" value="Nuevo subnivel" />
                <x-text-input :id="'nombre-subnivel-create-'.$nivel->id" class="mt-1 block w-full" type="text" name="nombre" :value="$subnivelCreateNombre" required />
                <x-input-error class="mt-2" :messages="$subnivelCreateBag->get('nombre')" />
            </div>
            <div class="md:w-28">
                <x-input-label :for="'siglas-subnivel-create-'.$nivel->id" value="Siglas" />
                <x-text-input :id="'siglas-subnivel-create-'.$nivel->id" class="mt-1 block w-full" type="text" name="siglas" :value="$subnivelCreateSiglas" maxlength="10" />
                <x-input-error class="mt-2" :messages="$subnivelCreateBag->get('siglas')" />
            </div>
            <div class="md:min-w-48">
                <x-input-label :for="'tipo-calificacion-subnivel-create-'.$nivel->id" value="Tipo de calificación" />
                <select
                    id="tipo-calificacion-subnivel-create-{{ $nivel->id }}"
                    name="tipo_calificacion"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option value="">Selecciona un tipo</option>
                    @foreach (TipoCalificacion::cases() as $option)
                        <option value="{{ $option->value }}" @selected((string) $subnivelCreateTipo === $option->value)>
                            {{ $option->label() }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$subnivelCreateBag->get('tipo_calificacion')" />
            </div>
            <div class="md:min-w-40 md:flex-1">
                <x-input-label :for="'descripcion-subnivel-create-'.$nivel->id" value="Descripción" />
                <x-text-input :id="'descripcion-subnivel-create-'.$nivel->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$subnivelCreateDescripcion" />
                <x-input-error class="mt-2" :messages="$subnivelCreateBag->get('descripcion')" />
            </div>
            <x-primary-button>Crear subnivel</x-primary-button>
        </form>
    </div>
</article>
