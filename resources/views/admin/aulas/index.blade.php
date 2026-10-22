@php
    $pestanaInicial = $errors->getBag('aula-docentes')->isNotEmpty()
        ? 'docentes'
        : (string) session('pestana', 'configuracion');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Aulas
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $establecimiento->nombre }}
            </p>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ pestana: @js($pestanaInicial) }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'aula-created')
                <p class="mb-4 text-sm font-medium text-green-700">
                    @if ((int) session('aulas_creadas', 1) === 1)
                        Aula creada correctamente.
                    @else
                        Se crearon {{ (int) session('aulas_creadas') }} aulas.
                    @endif
                </p>
            @endif

            @if (session('status') === 'aula-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Paralelo actualizado correctamente.</p>
            @endif

            @if (session('status') === 'aula-deleted')
                <p class="mb-4 text-sm font-medium text-green-700">Aula eliminada correctamente.</p>
            @endif

            @if (session('status') === 'aula-docentes-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Docentes asignados correctamente.</p>
            @endif

            @if (session('error'))
                <p class="mb-4 text-sm font-medium text-red-700">{{ session('error') }}</p>
            @endif

            @if ($oferta === null)
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    Sistemas aún no ha asignado modalidades y jornadas a este establecimiento.
                </p>
            @elseif ($periodo === null)
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    No existe ningún periodo activo.
                </p>
            @else
                <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm text-slate-600">
                        Configura los paralelos de cada grado y asigna el docente de cada asignatura.
                    </p>
                    <p class="mt-2 text-sm font-medium text-slate-800">
                        {{ $oferta->etiqueta() }}
                        · {{ $periodo->nombre }}
                    </p>
                </section>

                <style>
                    .estructura-folders {
                        display: flex;
                        align-items: flex-end;
                        gap: 0.25rem;
                        overflow-x: auto;
                        padding: 0 0.5rem;
                    }

                    .estructura-folder {
                        position: relative;
                        display: flex;
                        flex-shrink: 0;
                        align-items: center;
                        gap: 0.5rem;
                        margin-bottom: -1px;
                        border: 1px solid #e2e8f0;
                        border-bottom: 0;
                        border-radius: 0.75rem 0.75rem 0 0;
                        background: #f1f5f9;
                        padding: 0.5rem 1rem 0.625rem;
                        font-size: 0.875rem;
                        font-weight: 600;
                        color: #64748b;
                        clip-path: polygon(10px 0, calc(100% - 10px) 0, 100% 100%, 0 100%);
                    }

                    .estructura-folder.is-active {
                        z-index: 1;
                        background: #fff;
                        border-color: #cbd5e1;
                        color: #0f172a;
                        padding-top: 0.75rem;
                        padding-bottom: 0.75rem;
                        box-shadow: 0 -6px 14px rgb(15 23 42 / 0.08);
                    }

                    .estructura-folder-panel {
                        overflow: hidden;
                        border: 1px solid #cbd5e1;
                        border-radius: 1rem;
                        background: #f1f5f9;
                        box-shadow: 0 1px 2px rgb(15 23 42 / 0.05);
                    }
                </style>

                <nav class="estructura-folders" role="tablist" aria-label="Aulas">
                    <button
                        type="button"
                        role="tab"
                        class="estructura-folder"
                        :class="{ 'is-active': pestana === 'configuracion' }"
                        :aria-selected="pestana === 'configuracion'"
                        @click="pestana = 'configuracion'"
                    >
                        Configuración
                    </button>
                    <button
                        type="button"
                        role="tab"
                        class="estructura-folder"
                        :class="{ 'is-active': pestana === 'docentes' }"
                        :aria-selected="pestana === 'docentes'"
                        @click="pestana = 'docentes'"
                    >
                        Asignar docentes
                    </button>
                    <button
                        type="button"
                        role="tab"
                        class="estructura-folder"
                        :class="{ 'is-active': pestana === 'horarios' }"
                        :aria-selected="pestana === 'horarios'"
                        @click="pestana = 'horarios'"
                    >
                        Horarios
                    </button>
                </nav>

                <div class="estructura-folder-panel">
                    <div role="tabpanel" class="p-3" x-show="pestana === 'configuracion'" x-cloak>
                        @if ($grados->isEmpty())
                            <p class="rounded-xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                                Define los grados de esta modalidad y jornada en
                                <a href="{{ route('Admin.estructura.edit', $oferta) }}" class="font-medium text-indigo-600 hover:text-indigo-500">Estructura</a>
                                antes de crear aulas.
                            </p>
                        @else
                            <div class="flex flex-col gap-3">
                                @foreach ($grados as $grado)
                                    @php
                                        $aulasDelGrado = $aulasPorGrado->get($grado->id, collect())->sortBy('paralelo', SORT_NATURAL)->values();
                                    @endphp
                                    <article class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center">
                                        <h3 class="shrink-0 text-sm font-semibold text-slate-800 sm:w-48">
                                            {{ $grado->etiqueta() }}
                                        </h3>
                                        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                                            @forelse ($aulasDelGrado as $aula)
                                                <div class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white p-1">
                                                    <button
                                                        type="button"
                                                        class="rounded-md px-2.5 py-1 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                                                        x-on:click="$dispatch('aula-edit', {
                                                            id: {{ $aula->id }},
                                                            paralelo: @js($aula->paralelo),
                                                            grado: @js($grado->etiqueta()),
                                                            action: @js(route('Admin.aulas.update', $aula)),
                                                            destroyAction: @js(route('Admin.aulas.destroy', $aula)),
                                                        })"
                                                    >
                                                        {{ $aula->paralelo }}
                                                    </button>
                                                    <a
                                                        href="{{ route('Admin.aulas.horario', $aula) }}"
                                                        class="rounded-md bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                                    >
                                                        Crear horario
                                                    </a>
                                                </div>
                                            @empty
                                                <span class="text-sm text-slate-400">Sin paralelos</span>
                                            @endforelse
                                        </div>
                                        <x-primary-button
                                            type="button"
                                            class="shrink-0"
                                            x-on:click="$dispatch('aula-create', { id: '{{ $grado->id }}', nombre: {{ \Illuminate\Support\Js::from($grado->etiqueta()) }} })"
                                        >
                                            Crear aula
                                        </x-primary-button>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div role="tabpanel" class="p-3" x-show="pestana === 'docentes'" x-cloak>
                        @if ($aulas->isEmpty())
                            <p class="rounded-xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                                Aún no hay aulas creadas. Usa Configuración para crear los paralelos.
                            </p>
                        @else
                            <div class="flex flex-col gap-3">
                                @foreach ($aulas as $aula)
                                    <article class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                                        <h3 class="text-sm font-semibold text-slate-800">
                                            {{ $aula->etiqueta() }}
                                        </h3>
                                        <x-primary-button
                                            type="button"
                                            x-on:click="$dispatch('aula-docentes', { id: '{{ $aula->id }}', gradoId: '{{ $aula->establecimiento_grado_id }}' })"
                                        >
                                            Docentes
                                        </x-primary-button>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div role="tabpanel" class="p-3" x-show="pestana === 'horarios'" x-cloak>
                        @if ($aulas->isEmpty())
                            <p class="rounded-xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                                Aún no hay aulas creadas. Usa Configuración para crear los paralelos.
                            </p>
                        @else
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                @foreach ($aulas as $aula)
                                    <article class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                        <div>
                                            <h3 class="text-sm font-semibold text-slate-800">{{ $aula->etiqueta() }}</h3>
                                            <p class="mt-1 text-xs text-slate-500">Administra asignaturas y horas de clase.</p>
                                        </div>
                                        <a
                                            href="{{ route('Admin.aulas.horario', $aula) }}"
                                            class="shrink-0 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                        >
                                            Crear horario
                                        </a>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                @if ($grados->isNotEmpty())
                    @include('admin.aulas._aula-create-modal')
                    @include('admin.aulas._aula-edit-modal')
                    @include('admin.aulas._aula-docentes-modal')
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
