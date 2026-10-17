@php
    $mostrarCrearPrograma = $errors->any() && old('_form') === 'create';
@endphp

<x-modal name="programa-create" maxWidth="2xl" :show="$mostrarCrearPrograma" focusable>
    <form method="POST" action="{{ route('Admin.programas.store') }}" class="p-6">
        @csrf
        <input type="hidden" name="_form" value="create">

        <h3 class="text-lg font-semibold text-slate-800">Nuevo programa</h3>
        <p class="mt-1 text-sm text-slate-500">
            Puedes crear varios programas en {{ $periodo->nombre }}. Luego elige los alumnos de cada uno.
        </p>

        <div class="mt-4 flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <x-input-label for="tipo" value="Tipo" />
                <select id="tipo" name="tipo" class="rounded-lg border-slate-300 text-sm shadow-sm">
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo->value }}" @selected((string) old('tipo', $tipos[0]->value) === $tipo->value)>{{ $tipo->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <x-input-label for="nombre" value="Nombre" />
                <x-text-input id="nombre" name="nombre" type="text" class="block w-full" value="{{ old('nombre') }}" placeholder="Bachillerato Internacional" />
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
            <x-primary-button>Crear programa</x-primary-button>
        </div>
    </form>
</x-modal>
