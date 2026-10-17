@php
    $mostrarEditarPrograma = $errors->any() && old('_form') === 'edit';
    $programaEdicionId = (int) old('programa_id', 0);
@endphp

<x-modal name="programa-edit" maxWidth="2xl" :show="$mostrarEditarPrograma" focusable>
    <form
        method="POST"
        action="{{ $programaEdicionId > 0 ? route('Admin.programas.update', $programaEdicionId) : '#' }}"
        class="p-6"
        x-data="{
            action: @js($programaEdicionId > 0 ? route('Admin.programas.update', $programaEdicionId) : ''),
            programaId: @js($programaEdicionId),
            tipo: @js((string) old('tipo', '')),
            nombre: @js((string) old('nombre', '')),
        }"
        x-bind:action="action"
        x-on:abrir-programa-editar.window="
            action = $event.detail.action;
            programaId = $event.detail.programaId;
            tipo = $event.detail.tipo;
            nombre = $event.detail.nombre;
            $dispatch('open-modal', 'programa-edit');
        "
    >
        @csrf
        @method('PUT')
        <input type="hidden" name="_form" value="edit">
        <input type="hidden" name="programa_id" x-bind:value="programaId">

        <h3 class="text-lg font-semibold text-slate-800">Editar programa</h3>
        <p class="mt-1 text-sm text-slate-500">Cambia el tipo o el nombre. Los alumnos se gestionan aparte.</p>

        <div class="mt-4 flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <x-input-label for="edit-tipo" value="Tipo" />
                <select id="edit-tipo" name="tipo" class="rounded-lg border-slate-300 text-sm shadow-sm" x-model="tipo">
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <x-input-label for="edit-nombre" value="Nombre" />
                <x-text-input id="edit-nombre" name="nombre" type="text" class="block w-full" x-model="nombre" placeholder="Bachillerato Internacional" />
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
            <x-primary-button>Guardar cambios</x-primary-button>
        </div>
    </form>
</x-modal>
