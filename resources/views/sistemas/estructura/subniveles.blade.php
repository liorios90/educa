<x-app-layout>
    @php
        $editingSubnivelId = null;
        $openGradosId = $openGradosId ?? null;

        foreach ($nivel->subniveles as $subnivel) {
            if ($errors->getBag('subnivel-'.$subnivel->id)->isNotEmpty()) {
                $editingSubnivelId = $subnivel->id;
            }

            if ($errors->getBag('grado-create-'.$subnivel->id)->isNotEmpty()) {
                $openGradosId = $subnivel->id;
            }

            foreach ($subnivel->grados as $grado) {
                if ($errors->getBag('grado-'.$grado->id)->isNotEmpty()) {
                    $openGradosId = $subnivel->id;
                }
            }
        }
    @endphp

    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Subniveles
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $nivel->nombre }}
            </p>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ editingSubnivelId: @js($editingSubnivelId) }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @include('sistemas.estructura._status')

            <p class="mb-4 text-sm text-slate-500">
                <a href="{{ route('sistemas.estructura') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Niveles</a>
                <span class="text-slate-400"> / </span>
                {{ $nivel->nombre }}
            </p>

            <div class="mb-4 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('open-modal', 'subnivel-create-{{ $nivel->id }}')">
                    Crear subnivel
                </x-primary-button>
            </div>

            <div class="flex flex-col gap-4">
                @forelse ($nivel->subniveles as $subnivel)
                    @include('sistemas.estructura._subnivel', ['nivel' => $nivel, 'subnivel' => $subnivel])
                    @include('sistemas.estructura._grados-modal', [
                        'nivel' => $nivel,
                        'subnivel' => $subnivel,
                        'openGradosId' => $openGradosId,
                    ])
                @empty
                    <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                        Este nivel aún no tiene subniveles.
                    </p>
                @endforelse
            </div>

            @include('sistemas.estructura._subnivel-create-modal', ['nivel' => $nivel])

            <div class="mt-6">
                <a
                    href="{{ route('sistemas.estructura') }}"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    Volver
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
