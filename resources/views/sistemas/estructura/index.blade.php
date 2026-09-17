<x-app-layout>
    @php
        $openNivelId = $openNivelId ?: null;
        $openSubnivelId = $openSubnivelId ?: null;
        $editingNivelId = null;
        $editingSubnivelId = null;
        $editingGradoId = null;
        $nivelCreateBag = $errors->getBag('nivel-create');
        $nivelCreateNombre = $nivelCreateBag->isNotEmpty() ? old('nombre') : '';
        $nivelCreateSiglas = $nivelCreateBag->isNotEmpty() ? old('siglas') : '';
        $nivelCreateDescripcion = $nivelCreateBag->isNotEmpty() ? old('descripcion') : '';

        foreach ($niveles as $nivel) {
            if ($errors->getBag('nivel-'.$nivel->id)->isNotEmpty()) {
                $openNivelId = $nivel->id;
                $editingNivelId = $nivel->id;
            }

            if ($errors->getBag('subnivel-create-'.$nivel->id)->isNotEmpty()) {
                $openNivelId = $nivel->id;
            }

            foreach ($nivel->subniveles as $subnivel) {
                if ($errors->getBag('subnivel-'.$subnivel->id)->isNotEmpty()) {
                    $openNivelId = $nivel->id;
                    $openSubnivelId = $subnivel->id;
                    $editingSubnivelId = $subnivel->id;
                }

                if ($errors->getBag('grado-create-'.$subnivel->id)->isNotEmpty()) {
                    $openNivelId = $nivel->id;
                    $openSubnivelId = $subnivel->id;
                }

                foreach ($subnivel->grados as $grado) {
                    if ($errors->getBag('grado-'.$grado->id)->isNotEmpty()) {
                        $openNivelId = $nivel->id;
                        $openSubnivelId = $subnivel->id;
                        $editingGradoId = $grado->id;
                    }
                }
            }
        }
    @endphp

    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Niveles, subniveles y grados
        </h2>
    </x-slot>

    <div
        class="py-8"
        x-data="{
            openNivelId: @js($openNivelId),
            openSubnivelId: @js($openSubnivelId),
            editingNivelId: @js($editingNivelId),
            editingSubnivelId: @js($editingSubnivelId),
            editingGradoId: @js($editingGradoId),
            toggleNivel(id) {
                if (this.openNivelId === id) {
                    this.openNivelId = null
                    this.openSubnivelId = null
                    this.editingNivelId = null
                    this.editingSubnivelId = null
                    this.editingGradoId = null
                    return
                }

                this.openNivelId = id
            },
            editNivel(id) {
                this.openNivelId = id
                this.editingNivelId = id
            },
            toggleSubnivel(nivelId, subnivelId) {
                this.openNivelId = nivelId

                if (this.openSubnivelId === subnivelId) {
                    this.openSubnivelId = null
                    this.editingSubnivelId = null
                    this.editingGradoId = null
                    return
                }

                this.openSubnivelId = subnivelId
            },
            editSubnivel(nivelId, subnivelId) {
                this.openNivelId = nivelId
                this.openSubnivelId = subnivelId
                this.editingSubnivelId = subnivelId
            },
            editGrado(nivelId, subnivelId, gradoId) {
                this.openNivelId = nivelId
                this.openSubnivelId = subnivelId
                this.editingGradoId = gradoId
            },
        }"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'nivel-created')
                <p class="mb-4 text-sm font-medium text-green-700">Nivel creado correctamente. Ya puedes agregar subniveles.</p>
            @endif
            @if (session('status') === 'nivel-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Nivel actualizado correctamente.</p>
            @endif
            @if (session('status') === 'nivel-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Nivel eliminado correctamente.</p>
            @endif
            @if (session('status') === 'subnivel-created')
                <p class="mb-4 text-sm font-medium text-green-700">Subnivel creado correctamente. Ya puedes agregar grados.</p>
            @endif
            @if (session('status') === 'subnivel-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Subnivel actualizado correctamente.</p>
            @endif
            @if (session('status') === 'subnivel-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Subnivel eliminado correctamente.</p>
            @endif
            @if (session('status') === 'grado-created')
                <p class="mb-4 text-sm font-medium text-green-700">Grado creado correctamente.</p>
            @endif
            @if (session('status') === 'grado-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Grado actualizado correctamente.</p>
            @endif
            @if (session('status') === 'grado-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Grado eliminado correctamente.</p>
            @endif
            @if (session('error'))
                <p class="mb-4 text-sm font-medium text-red-700">{{ session('error') }}</p>
            @endif

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-slate-800">Nuevo nivel</h3>
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
