<x-app-layout>
    @php
        $editingNivelId = null;
        $nivelCreateBag = $errors->getBag('nivel-create');
        $nivelCreateNombre = $nivelCreateBag->isNotEmpty() ? old('nombre') : '';
        $nivelCreateSiglas = $nivelCreateBag->isNotEmpty() ? old('siglas') : '';
        $nivelCreateDescripcion = $nivelCreateBag->isNotEmpty() ? old('descripcion') : '';

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

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-slate-800">Nuevo nivel</h3>
                <p class="mt-2 text-sm text-slate-500">
                    Crea el nivel y luego entra a él para definir sus subniveles y grados.
                </p>
                <form method="POST" action="{{ route('sistemas.estructura.niveles.store') }}" class="mt-4 flex flex-col gap-4 md:flex-row md:flex-wrap md:items-end">
                    @csrf
                    <div class="md:min-w-48 md:flex-1">
                        <x-input-label for="nombre-nivel-create" value="Nombre" />
                        <x-text-input id="nombre-nivel-create" class="mt-1 block w-full" type="text" name="nombre" :value="$nivelCreateNombre" required />
                        <x-input-error class="mt-2" :messages="$errors->getBag('nivel-create')->get('nombre')" />
                    </div>
                    <div class="md:w-28">
                        <x-input-label for="siglas-nivel-create" value="Siglas" />
                        <x-text-input id="siglas-nivel-create" class="mt-1 block w-full" type="text" name="siglas" :value="$nivelCreateSiglas" maxlength="10" />
                        <x-input-error class="mt-2" :messages="$errors->getBag('nivel-create')->get('siglas')" />
                    </div>
                    <div class="md:min-w-48 md:flex-1">
                        <x-input-label for="descripcion-nivel-create" value="Descripción" />
                        <x-text-input id="descripcion-nivel-create" class="mt-1 block w-full" type="text" name="descripcion" :value="$nivelCreateDescripcion" />
                        <x-input-error class="mt-2" :messages="$errors->getBag('nivel-create')->get('descripcion')" />
                    </div>
                    <x-primary-button>Crear nivel</x-primary-button>
                </form>
            </section>

            <div class="flex flex-col gap-4">
                @forelse ($niveles as $nivel)
                    @include('sistemas.estructura._nivel', ['nivel' => $nivel])
                @empty
                    <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                        Aún no hay niveles. Crea el primero para poder agregar subniveles y grados.
                    </p>
                @endforelse
            </div>

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
