<x-app-layout>
    @php
        $editingNivelId = null;

        foreach ($niveles as $nivel) {
            if ($errors->getBag('nivel-'.$nivel->id)->isNotEmpty()) {
                $editingNivelId = $nivel->id;
            }
        }
    @endphp

    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Niveles
        </h2>
    </x-slot>

    <div class="py-8" x-data="{ editingNivelId: @js($editingNivelId) }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @include('sistemas.estructura._status')

            <div class="mb-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('open-modal', 'nivel-create')">
                    Crear nivel
                </x-primary-button>
            </div>

            <div class="flex flex-col gap-4">
                @forelse ($niveles as $nivel)
                    @include('sistemas.estructura._nivel', ['nivel' => $nivel])
                @empty
                    <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                        Aún no hay niveles. Crea el primero para poder agregar subniveles y grados.
                    </p>
                @endforelse
            </div>

            @include('sistemas.estructura._nivel-create-modal')

            @if ($backUrl)
                <div class="mt-6">
                    <a
                        href="{{ $backUrl }}"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        Volver
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
