@php
    $aulaCreateBag = $errors->getBag('aula-create');
    $aulaCreateGradoId = $aulaCreateBag->isNotEmpty() ? old('establecimiento_grado_id') : '';
    $aulaCreateParalelo = $aulaCreateBag->isNotEmpty() ? old('paralelo') : '';
@endphp

<x-modal name="aula-create" maxWidth="2xl" :show="$aulaCreateBag->isNotEmpty()" focusable>
    <form method="POST" action="{{ route('Admin.aulas.store') }}" class="p-6">
        @csrf

        <h3 class="text-lg font-semibold text-slate-800">Nueva aula</h3>
        <p class="mt-1 text-sm text-slate-500">
            {{ $oferta->etiqueta() }} · {{ $periodo->nombre }}
        </p>

        <div class="mt-4 flex flex-col gap-4">
            <div>
                <x-input-label for="grado-aula-create" value="Grado" />
                <select
                    id="grado-aula-create"
                    name="establecimiento_grado_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option value="">Selecciona un grado</option>
                    @foreach ($grados as $grado)
                        <option value="{{ $grado->id }}" @selected((string) $aulaCreateGradoId === (string) $grado->id)>
                            {{ $grado->etiqueta() }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$aulaCreateBag->get('establecimiento_grado_id')" />
            </div>
            <div>
                <x-input-label for="paralelo-aula-create" value="Paralelo" />
                <x-text-input id="paralelo-aula-create" class="mt-1 block w-full" type="text" name="paralelo" :value="$aulaCreateParalelo" maxlength="10" required />
                <x-input-error class="mt-2" :messages="$aulaCreateBag->get('paralelo')" />
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
            <x-primary-button>Crear aula</x-primary-button>
        </div>
    </form>
</x-modal>
