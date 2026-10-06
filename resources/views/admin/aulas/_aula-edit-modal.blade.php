@php
    $aulaEditBag = $errors->getBag('aula-edit');
    $aulaEditId = (int) old('aula_id');
    $aulaEnEdicion = $aulasPorGrado->flatten(1)->firstWhere('id', $aulaEditId);
    $gradoEnEdicion = $aulaEnEdicion === null
        ? null
        : $grados->firstWhere('id', $aulaEnEdicion->establecimiento_grado_id);
    $aulaEditParalelo = $aulaEditBag->isNotEmpty()
        ? (string) old('paralelo', $aulaEnEdicion?->paralelo ?? '')
        : '';
    $aulaEditGrado = $gradoEnEdicion?->etiqueta() ?? '';
    $aulaEditAction = $aulaEnEdicion === null ? '' : route('Admin.aulas.update', $aulaEnEdicion);
    $aulaDestroyAction = $aulaEnEdicion === null ? '' : route('Admin.aulas.destroy', $aulaEnEdicion);
@endphp

<x-modal name="aula-edit" maxWidth="lg" :show="$aulaEditBag->isNotEmpty() && $aulaEnEdicion !== null" focusable>
    <div
        class="p-6"
        x-data="{
            aulaId: @js($aulaEnEdicion?->id ?? ''),
            paralelo: @js($aulaEditParalelo),
            gradoNombre: @js($aulaEditGrado),
            action: @js($aulaEditAction),
            destroyAction: @js($aulaDestroyAction),
        }"
        x-on:aula-edit.window="
            aulaId = $event.detail.id;
            paralelo = $event.detail.paralelo;
            gradoNombre = $event.detail.grado;
            action = $event.detail.action;
            destroyAction = $event.detail.destroyAction;
            $dispatch('open-modal', 'aula-edit');
        "
    >
        <form method="POST" x-bind:action="action" action="{{ $aulaEditAction }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="aula_id" x-bind:value="aulaId" value="{{ $aulaEnEdicion?->id }}">

            <h3 class="text-lg font-semibold text-slate-800">Editar paralelo</h3>
            <p class="mt-1 text-sm text-slate-500" x-text="gradoNombre">{{ $aulaEditGrado }}</p>

            <div class="mt-4">
                <x-input-label for="paralelo-aula-edit" value="Paralelo" />
                <x-text-input id="paralelo-aula-edit" class="mt-1 block w-full" type="text" name="paralelo" x-model="paralelo" :value="$aulaEditParalelo" maxlength="10" required />
                <x-input-error class="mt-2" :messages="$aulaEditBag->get('paralelo')" />
            </div>

            <div class="mt-6 flex items-center justify-between gap-2">
                <button
                    type="submit"
                    class="text-sm font-medium text-red-600 hover:text-red-500"
                    form="aula-destroy-form"
                    onclick="return confirm('¿Eliminar esta aula?')"
                >
                    Eliminar
                </button>
                <div class="flex gap-2">
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                    <x-primary-button>Guardar</x-primary-button>
                </div>
            </div>
        </form>
        <form id="aula-destroy-form" method="POST" x-bind:action="destroyAction" action="{{ $aulaDestroyAction }}">
            @csrf
            @method('DELETE')
        </form>
    </div>
</x-modal>
