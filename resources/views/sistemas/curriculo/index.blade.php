<x-app-layout>
    @php
        $openNivelId = $openNivelId ?: null;
        $openSubnivelId = $openSubnivelId ?: null;
        $openAreaId = $openAreaId ?: null;
        $editingAreaId = null;
        $editingAsignaturaId = null;

        foreach ($niveles as $nivel) {
            foreach ($nivel->subniveles as $subnivel) {
                if ($errors->getBag('area-create-'.$subnivel->id)->isNotEmpty()) {
                    $openNivelId = $nivel->id;
                    $openSubnivelId = $subnivel->id;
                }

                foreach ($subnivel->areas as $area) {
                    if ($errors->getBag('area-'.$area->id)->isNotEmpty()) {
                        $openNivelId = $nivel->id;
                        $openSubnivelId = $subnivel->id;
                        $openAreaId = $area->id;
                        $editingAreaId = $area->id;
                    }

                    if ($errors->getBag('asignatura-create-'.$area->id)->isNotEmpty()) {
                        $openNivelId = $nivel->id;
                        $openSubnivelId = $subnivel->id;
                        $openAreaId = $area->id;
                    }

                    foreach ($area->asignaturas as $asignatura) {
                        if ($errors->getBag('asignatura-'.$asignatura->id)->isNotEmpty()) {
                            $openNivelId = $nivel->id;
                            $openSubnivelId = $subnivel->id;
                            $openAreaId = $area->id;
                            $editingAsignaturaId = $asignatura->id;
                        }
                    }
                }
            }
        }
    @endphp

    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Áreas y asignaturas
        </h2>
    </x-slot>

    <div
        class="py-8"
        x-data="{
            openNivelId: @js($openNivelId),
            openSubnivelId: @js($openSubnivelId),
            openAreaId: @js($openAreaId),
            editingAreaId: @js($editingAreaId),
            editingAsignaturaId: @js($editingAsignaturaId),
            toggleNivel(id) {
                if (this.openNivelId === id) {
                    this.openNivelId = null
                    this.openSubnivelId = null
                    this.openAreaId = null
                    this.editingAreaId = null
                    this.editingAsignaturaId = null
                    return
                }

                this.openNivelId = id
            },
            toggleSubnivel(nivelId, subnivelId) {
                this.openNivelId = nivelId

                if (this.openSubnivelId === subnivelId) {
                    this.openSubnivelId = null
                    this.openAreaId = null
                    this.editingAreaId = null
                    this.editingAsignaturaId = null
                    return
                }

                this.openSubnivelId = subnivelId
            },
            toggleArea(nivelId, subnivelId, areaId) {
                this.openNivelId = nivelId
                this.openSubnivelId = subnivelId

                if (this.openAreaId === areaId) {
                    this.openAreaId = null
                    this.editingAreaId = null
                    this.editingAsignaturaId = null
                    return
                }

                this.openAreaId = areaId
            },
            editArea(nivelId, subnivelId, areaId) {
                this.openNivelId = nivelId
                this.openSubnivelId = subnivelId
                this.openAreaId = areaId
                this.editingAreaId = areaId
            },
            editAsignatura(nivelId, subnivelId, areaId, asignaturaId) {
                this.openNivelId = nivelId
                this.openSubnivelId = subnivelId
                this.openAreaId = areaId
                this.editingAsignaturaId = asignaturaId
            },
        }"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'area-created')
                <p class="mb-4 text-sm font-medium text-green-700">Área creada correctamente. Ya puedes agregar asignaturas.</p>
            @endif
            @if (session('status') === 'area-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Área actualizada correctamente.</p>
            @endif
            @if (session('status') === 'area-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Área eliminada correctamente.</p>
            @endif
            @if (session('status') === 'asignatura-created')
                <p class="mb-4 text-sm font-medium text-green-700">Asignatura creada correctamente.</p>
            @endif
            @if (session('status') === 'asignatura-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Asignatura actualizada correctamente.</p>
            @endif
            @if (session('status') === 'asignatura-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Asignatura eliminada correctamente.</p>
            @endif
            @if (session('error'))
                <p class="mb-4 text-sm font-medium text-red-700">{{ session('error') }}</p>
            @endif

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-600">
                    Configura las áreas (ámbitos en Inicial) y las asignaturas de cada subnivel. En las libretas del Ministerio de Educación se agrupan por área y luego por asignatura. Más adelante estas asignaturas se podrán asignar a los grados del mismo subnivel.
                </p>
                <p class="mt-2 text-sm text-slate-500">
                    Los niveles, subniveles y grados se administran en
                    <a href="{{ route('sistemas.estructura') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Nivel - Subnivel - Grado</a>.
                </p>
            </section>

            <div class="flex flex-col gap-4">
                @forelse ($niveles as $nivel)
                    @include('sistemas.curriculo._nivel', ['nivel' => $nivel])
                @empty
                    <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                        Aún no hay niveles. Créalos primero en
                        <a href="{{ route('sistemas.estructura') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Nivel - Subnivel - Grado</a>
                        para poder configurar áreas y asignaturas.
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
