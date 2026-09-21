@php
    $areaCreateBag = $errors->getBag('area-create-'.$subnivel->id);
    $areaCreateCodigo = $areaCreateBag->isNotEmpty() ? old('codigo') : '';
    $areaCreateNombre = $areaCreateBag->isNotEmpty() ? old('nombre') : '';
    $areaCreateDescripcion = $areaCreateBag->isNotEmpty() ? old('descripcion') : '';
    $areaCreateOrden = $areaCreateBag->isNotEmpty() ? old('orden', 0) : 0;
    $areaCreateLibreta = $areaCreateBag->isNotEmpty()
        ? (bool) old('aparece_en_libreta', true)
        : true;
@endphp

<section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <button
            type="button"
            class="flex min-w-0 flex-1 items-start gap-3 text-left"
            @click="toggleSubnivel({{ $nivel->id }}, {{ $subnivel->id }})"
        >
            <span
                class="mt-1 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition"
                :class="{ 'rotate-180 bg-indigo-100 text-indigo-700': openSubnivelId === {{ $subnivel->id }} }"
            >
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </span>
            <span class="min-w-0">
                <span class="block font-medium text-slate-800">
                    {{ $subnivel->nombre }}
                    @if ($subnivel->siglas)
                        <span class="ml-1 font-normal text-slate-500">({{ $subnivel->siglas }})</span>
                    @endif
                </span>
                <span class="mt-1 block text-sm text-slate-500">{{ $subnivel->descripcion ?: 'Sin descripción' }}</span>
                <span class="mt-1 block text-xs text-slate-400">
                    {{ $subnivel->areas->count() }} áreas
                    · {{ $subnivel->areas->sum(fn ($area) => $area->asignaturas->count()) }} asignaturas
                </span>
            </span>
        </button>
    </div>

    <div class="space-y-4 border-t border-slate-100 bg-slate-50 px-4 py-4" x-cloak x-show="openSubnivelId === {{ $subnivel->id }}">
        <div class="flex flex-col gap-3">
            <h5 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Áreas</h5>

            @forelse ($subnivel->areas as $area)
                @include('sistemas.curriculo._area', ['nivel' => $nivel, 'subnivel' => $subnivel, 'area' => $area])
            @empty
                <p class="rounded-lg border border-dashed border-slate-200 bg-white px-4 py-3 text-sm text-slate-500">
                    Este subnivel aún no tiene áreas. En Inicial usa ámbitos; en EGB y BGU usa las áreas del currículo nacional.
                </p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('sistemas.curriculo.areas.store', [$nivel, $subnivel]) }}" class="flex flex-col gap-4 rounded-lg border border-indigo-100 bg-white p-4 md:flex-row md:flex-wrap md:items-end">
            @csrf
            <div class="md:w-28">
                <x-input-label :for="'codigo-area-create-'.$subnivel->id" value="Código" />
                <x-text-input :id="'codigo-area-create-'.$subnivel->id" class="mt-1 block w-full" type="text" name="codigo" :value="$areaCreateCodigo" maxlength="20" />
                <x-input-error class="mt-2" :messages="$areaCreateBag->get('codigo')" />
            </div>
            <div class="md:min-w-40 md:flex-1">
                <x-input-label :for="'nombre-area-create-'.$subnivel->id" value="Nueva área" />
                <x-text-input :id="'nombre-area-create-'.$subnivel->id" class="mt-1 block w-full" type="text" name="nombre" :value="$areaCreateNombre" required />
                <x-input-error class="mt-2" :messages="$areaCreateBag->get('nombre')" />
            </div>
            <div class="md:min-w-40 md:flex-1">
                <x-input-label :for="'descripcion-area-create-'.$subnivel->id" value="Descripción" />
                <x-text-input :id="'descripcion-area-create-'.$subnivel->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$areaCreateDescripcion" />
                <x-input-error class="mt-2" :messages="$areaCreateBag->get('descripcion')" />
            </div>
            <div class="md:w-24">
                <x-input-label :for="'orden-area-create-'.$subnivel->id" value="Orden" />
                <x-text-input :id="'orden-area-create-'.$subnivel->id" class="mt-1 block w-full" type="number" name="orden" :value="$areaCreateOrden" min="0" max="999" required />
                <x-input-error class="mt-2" :messages="$areaCreateBag->get('orden')" />
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700 md:mb-1">
                <input type="hidden" name="aparece_en_libreta" value="0">
                <input
                    id="libreta-area-create-{{ $subnivel->id }}"
                    type="checkbox"
                    name="aparece_en_libreta"
                    value="1"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    @checked($areaCreateLibreta)
                >
                En libreta
            </label>
            <x-primary-button>Crear área</x-primary-button>
        </form>
    </div>
</section>
