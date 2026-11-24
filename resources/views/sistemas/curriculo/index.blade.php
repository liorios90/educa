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
            {{ $porAmbitos ? 'Ámbitos y Destrezas' : 'Áreas y asignaturas' }}
        </h2>
    </x-slot>

    <div
        class="py-8"
        x-data="{
            openNivelId: @js($openNivelId),
            openSubnivelId: @js($openSubnivelId),
            editingAreaId: @js($editingAreaId),
            editingAsignaturaId: @js($editingAsignaturaId),
            toggleNivel(id) {
                if (this.openNivelId === id) {
                    this.openNivelId = null
                    this.openSubnivelId = null
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
                    this.editingAreaId = null
                    this.editingAsignaturaId = null
                    return
                }

                this.openSubnivelId = subnivelId
            },
            editArea(nivelId, subnivelId, areaId) {
                this.openNivelId = nivelId
                this.openSubnivelId = subnivelId
                this.editingAreaId = this.editingAreaId === areaId ? null : areaId
            },
            editAsignatura(asignaturaId) {
                this.editingAsignaturaId = this.editingAsignaturaId === asignaturaId ? null : asignaturaId
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
            @if (session('status') === 'destreza-created')
                <p class="mb-4 text-sm font-medium text-green-700">Destreza creada. Agrega sus indicadores de evaluación.</p>
            @endif
            @if (session('status') === 'destreza-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Destreza actualizada.</p>
            @endif
            @if (session('status') === 'destreza-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Destreza eliminada.</p>
            @endif
            @if (session('status') === 'indicador-created')
                <p class="mb-4 text-sm font-medium text-green-700">Indicador de evaluación agregado.</p>
            @endif
            @if (session('status') === 'indicador-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Indicador de evaluación actualizado.</p>
            @endif
            @if (session('status') === 'indicador-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Indicador de evaluación eliminado.</p>
            @endif
            @if (session('status') === 'organizacion-updated')
                <p class="mb-4 text-sm font-medium text-green-700">La organización del subnivel quedó actualizada.</p>
            @endif
            @if (session('error'))
                <p class="mb-4 text-sm font-medium text-red-700">{{ session('error') }}</p>
            @endif

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @if ($porAmbitos)
                    <p class="text-sm text-slate-600">
                        Subniveles organizados por ámbitos, como Inicial y Preparatoria en el currículo del Ministerio de Educación. Cada eje agrupa ámbitos con sus criterios, destrezas e indicadores. La evaluación es cualitativa y no se promedia.
                    </p>
                @else
                    <p class="text-sm text-slate-600">
                        Subniveles organizados por áreas, como Elemental, Media, Superior y Bachillerato. En las libretas del Ministerio de Educación se agrupan por área y luego por asignatura. En cada asignatura eliges si la calificación es cuantitativa o cualitativa. Si es cualitativa, puedes activar los criterios de evaluación.
                    </p>
                    <p class="mt-2 text-sm text-slate-600">Marca <strong>Área técnica</strong> en las áreas del subnivel Bachillerato General Unificado para publicarlas en el catálogo. Cada establecimiento activará únicamente las áreas técnicas que ofrezca.</p>
                @endif
                <p class="mt-2 text-sm text-slate-500">
                    Si un subnivel debe cambiar de áreas a ámbitos, o al revés, se define en
                    <a href="{{ route('sistemas.estructura') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Nivel - Subnivel - Grado</a>.
                </p>
            </section>

            <div class="flex flex-col gap-4">
                @forelse ($niveles as $nivel)
                    @include('sistemas.curriculo._nivel', ['nivel' => $nivel])
                @empty
                    <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                        @if ($porAmbitos)
                            Ningún subnivel se organiza por ámbitos. Elige Ámbitos en
                        @else
                            Ningún subnivel se organiza por áreas. Elige Áreas en
                        @endif
                        <a href="{{ route('sistemas.estructura') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Nivel - Subnivel - Grado</a>.
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
