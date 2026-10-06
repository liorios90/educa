@php
    $aulaCreateBag = $errors->getBag('aula-create');
    $aulaCreateGradoId = $aulaCreateBag->isNotEmpty() ? (string) old('establecimiento_grado_id', '') : '';
    $aulaCreateGrado = $grados->firstWhere('id', (int) $aulaCreateGradoId);
    $aulaCreateGradoNombre = $aulaCreateGrado?->etiqueta() ?? '';
    $aulaCreateParalelos = '';

    if ($aulaCreateBag->isNotEmpty()) {
        $paralelosAnteriores = old('paralelos', '');
        $aulaCreateParalelos = is_array($paralelosAnteriores)
            ? implode(', ', $paralelosAnteriores)
            : (string) $paralelosAnteriores;
    }
@endphp

<x-modal name="aula-create" maxWidth="lg" :show="$aulaCreateBag->isNotEmpty()" focusable>
    <div
        class="p-6"
        x-data="{
            gradoId: @js($aulaCreateGradoId),
            gradoNombre: @js($aulaCreateGradoNombre),
            paralelos: @js($aulaCreateParalelos),
        }"
        x-on:aula-create.window="
            gradoId = String($event.detail.id);
            gradoNombre = $event.detail.nombre;
            paralelos = '';
            $dispatch('open-modal', 'aula-create');
        "
    >
        <form method="POST" action="{{ route('Admin.aulas.store') }}">
            @csrf
            <input type="hidden" name="establecimiento_grado_id" x-bind:value="gradoId" value="{{ $aulaCreateGradoId }}">

            <h3 class="text-lg font-semibold text-slate-800">Nueva aula</h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ $oferta->etiqueta() }} · {{ $periodo->nombre }}
            </p>
            <p class="mt-2 text-sm font-medium text-slate-800" x-text="gradoNombre">{{ $aulaCreateGradoNombre }}</p>

            <div class="mt-4">
                <x-input-label for="paralelos-aula-create" value="Paralelos" />
                <x-text-input id="paralelos-aula-create" class="mt-1 block w-full" type="text" name="paralelos" x-model="paralelos" :value="$aulaCreateParalelos" maxlength="120" placeholder="A, B, C" required />
                <p class="mt-1 text-xs text-slate-500">Escribe uno o más, separados por coma. Se crea un aula por cada paralelo.</p>
                <x-input-error class="mt-2" :messages="array_merge($aulaCreateBag->get('paralelos'), $aulaCreateBag->get('paralelos.*'))" />
                <x-input-error class="mt-2" :messages="$aulaCreateBag->get('establecimiento_grado_id')" />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button>Crear aulas</x-primary-button>
            </div>
        </form>
    </div>
</x-modal>
