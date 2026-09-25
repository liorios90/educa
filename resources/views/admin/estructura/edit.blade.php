<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Estructura educativa
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $establecimiento->nombre }} · {{ $oferta->etiqueta() }}
            </p>
        </div>
    </x-slot>

    @php
        $activeNivelId = $niveles->first()?->id;

        foreach ($niveles as $nivel) {
            $gradoIdsDelNivel = $nivel->subniveles
                ->flatMap(fn ($subnivel) => $subnivel->grados)
                ->pluck('id')
                ->map(fn ($id) => (string) $id);

            if ($gradoIdsDelNivel->intersect($selectedGradoIds)->isNotEmpty()) {
                $activeNivelId = $nivel->id;
                break;
            }
        }
    @endphp

    <div
        class="py-8"
        x-data="{
            activeNivelId: @js($activeNivelId),
            originalGrados: @js($selectedGradoIds),
            grados: @js($selectedGradoIds),
            selectedIn(ids) {
                return ids.filter((id) => this.grados.includes(String(id))).length
            },
            selectedLabel(ids) {
                const count = this.selectedIn(ids)

                return count === 1 ? '1 grado' : count + ' grados'
            },
            hasChanges(ids) {
                const selected = (list) => ids.filter((id) => list.includes(String(id))).sort().join(',')

                return selected(this.grados) !== selected(this.originalGrados)
            },
        }"
    >
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'estructura-updated')
                <p class="mb-4 text-sm font-medium text-green-700">Estructura de la jornada guardada correctamente.</p>
            @endif

            <p class="mb-4">
                <a href="{{ route('Admin.estructura') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    Volver a modalidades y jornadas
                </a>
            </p>

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-slate-800">Catálogo nacional</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Cada pestaña es un nivel de Sistemas. Marca los grados que ofrece esta jornada.
                    El subnivel y el nivel se graban solos. El nombre oficial queda para reportes;
                    si quieres, puedes poner un nombre propio para usar dentro del colegio.
                </p>
            </section>

            <x-input-error class="mb-4" :messages="$errors->get('grados')" />
            <x-input-error class="mb-4" :messages="$errors->get('nombre_grados')" />
            <x-input-error class="mb-4" :messages="$errors->get('nombre_subniveles')" />

            <form method="POST" action="{{ route('Admin.estructura.update', $oferta) }}">
                @csrf
                @method('PUT')

                @if ($niveles->isEmpty())
                    <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                        Sistemas aún no ha definido la estructura nacional de niveles, subniveles y grados.
                    </p>
                @else
                    <style>
                        .estructura-folders {
                            display: flex;
                            align-items: flex-end;
                            gap: 0.25rem;
                            overflow-x: auto;
                            overflow-y: hidden;
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
                            transition: background-color 150ms ease, color 150ms ease, box-shadow 150ms ease;
                        }

                        .estructura-folder:hover {
                            background: #e2e8f0;
                            color: #334155;
                        }

                        .estructura-folder.is-filled {
                            background: #e2e8f0;
                            border-color: #cbd5e1;
                            color: #1e293b;
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

                        .estructura-folder.is-active::after {
                            content: "";
                            position: absolute;
                            right: 0;
                            bottom: -1px;
                            left: 0;
                            height: 2px;
                            background: #fff;
                        }

                        .estructura-folder:focus {
                            outline: none;
                        }

                        .estructura-folder:focus-visible {
                            box-shadow: 0 0 0 2px #0f172a;
                        }

                        .estructura-folder-count {
                            border-radius: 9999px;
                            background: #fff;
                            padding: 0.125rem 0.5rem;
                            font-size: 0.75rem;
                            font-weight: 500;
                            color: #64748b;
                            box-shadow: inset 0 0 0 1px #e2e8f0;
                        }

                        .estructura-folder.is-filled .estructura-folder-count {
                            background: #0f172a;
                            color: #fff;
                            box-shadow: none;
                        }

                        .estructura-folder.is-active.is-filled .estructura-folder-count {
                            background: #c9a227;
                            color: #fff;
                            box-shadow: none;
                        }

                        .estructura-folder.is-active.is-dirty .estructura-folder-count {
                            background: #b8860b;
                            color: #fff;
                            box-shadow: none;
                        }

                        .estructura-folder-panel {
                            overflow: hidden;
                            border: 1px solid #cbd5e1;
                            border-radius: 1rem;
                            background: #fff;
                            box-shadow: 0 1px 2px rgb(15 23 42 / 0.05);
                        }

                        .estructura-nivel-title {
                            margin: 0;
                            color: navy;
                            font-size: 1.125rem;
                            font-weight: 700;
                            line-height: 1.4;
                            text-align: center;
                        }

                        .estructura-subniveles {
                            display: flex;
                            flex-direction: column;
                            gap: 0.75rem;
                            padding: 0.75rem;
                            background: #f1f5f9;
                        }

                        .estructura-subnivel {
                            border: 1px solid #cbd5e1;
                            border-radius: 0.75rem;
                            background: #fff;
                            padding: 1rem 1.25rem;
                        }

                        .estructura-subnivel-row {
                            display: flex;
                            align-items: center;
                            gap: 0.75rem;
                            margin-bottom: 0.75rem;
                            padding-bottom: 0.5rem;
                            border-bottom: 2px solid navy;
                        }

                        .estructura-subnivel-title {
                            margin: 0;
                            flex-shrink: 0;
                            color: #0f172a;
                            font-size: 0.875rem;
                            font-weight: 700;
                            line-height: 1.4;
                        }

                        .estructura-subnivel-nombre {
                            min-width: 0;
                            flex: 1 1 8rem;
                        }

                        .estructura-grado-card.is-compact {
                            width: fit-content;
                        }

                        .estructura-grado-row {
                            display: flex;
                            align-items: center;
                            gap: 0.75rem;
                        }

                        .estructura-grado-check {
                            display: flex;
                            flex-shrink: 0;
                            align-items: center;
                            gap: 0.75rem;
                        }

                        .estructura-grado-nombre {
                            min-width: 0;
                            flex: 1 1 8rem;
                        }
                    </style>

                    <div>
                        <nav class="estructura-folders" role="tablist" aria-label="Niveles del catálogo">
                            @foreach ($niveles as $nivel)
                                @php
                                    $gradoIdsDelNivel = $nivel->subniveles
                                        ->flatMap(fn ($subnivel) => $subnivel->grados)
                                        ->pluck('id')
                                        ->map(fn ($id) => (string) $id)
                                        ->values()
                                        ->all();
                                @endphp
                                <button
                                    type="button"
                                    role="tab"
                                    class="estructura-folder"
                                    :class="{
                                        'is-active': activeNivelId === {{ $nivel->id }},
                                        'is-filled': selectedIn(@js($gradoIdsDelNivel)) > 0,
                                        'is-dirty': hasChanges(@js($gradoIdsDelNivel)),
                                    }"
                                    :aria-selected="activeNivelId === {{ $nivel->id }}"
                                    @click="activeNivelId = {{ $nivel->id }}"
                                >
                                    <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                                    </svg>
                                    <span>{{ $nivel->nombre }}</span>
                                    <span
                                        class="estructura-folder-count"
                                        x-text="selectedLabel(@js($gradoIdsDelNivel))"
                                    ></span>
                                </button>
                            @endforeach
                        </nav>

                        <div class="estructura-folder-panel">
                        @foreach ($niveles as $nivel)
                            <div
                                role="tabpanel"
                                x-cloak
                                x-show="activeNivelId === {{ $nivel->id }}"
                            >
                                <header class="border-b border-slate-100 px-5 py-4">
                                    <h3 class="estructura-nivel-title">Nivel: {{ $nivel->nombre }}</h3>
                                </header>

                                @if ($nivel->subniveles->isEmpty())
                                    <p class="px-5 py-6 text-sm text-slate-500">Este nivel aún no tiene subniveles ni grados en el catálogo.</p>
                                @else
                                    <div class="estructura-subniveles">
                                        @foreach ($nivel->subniveles as $subnivel)
                                            @php
                                                $gradoIdsDelSubnivel = $subnivel->grados
                                                    ->pluck('id')
                                                    ->map(fn ($id) => (string) $id)
                                                    ->values()
                                                    ->all();
                                            @endphp
                                            <section class="estructura-subnivel">
                                                <div>
                                                    <div class="estructura-subnivel-row">
                                                        <h4 class="estructura-subnivel-title">Subnivel: {{ $subnivel->nombre }}</h4>
                                                        <input
                                                            id="nombre-subnivel-{{ $subnivel->id }}"
                                                            type="text"
                                                            name="nombre_subniveles[{{ $subnivel->id }}]"
                                                            value="{{ $nombresSubniveles[(string) $subnivel->id] ?? '' }}"
                                                            maxlength="150"
                                                            placeholder="Nombre en el establecimiento"
                                                            aria-label="Nombre en el establecimiento de {{ $subnivel->nombre }}"
                                                            class="estructura-subnivel-nombre rounded-md border-slate-300 text-sm"
                                                            x-cloak
                                                            x-show="selectedIn(@js($gradoIdsDelSubnivel)) > 0"
                                                        >
                                                    </div>
                                                    <x-input-error class="mt-1" :messages="$errors->get('nombre_subniveles.'.$subnivel->id)" />
                                                </div>

                                                @if ($subnivel->grados->isEmpty())
                                                    <p class="text-sm text-slate-500">Este subnivel aún no tiene grados en el catálogo.</p>
                                                @else
                                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Grados</p>
                                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                        @foreach ($subnivel->grados as $grado)
                                                            <div
                                                                class="estructura-grado-card rounded-xl border border-slate-200 px-3 py-2.5"
                                                                :class="{ 'is-compact': ! grados.includes(String({{ $grado->id }})) }"
                                                            >
                                                                <div class="estructura-grado-row">
                                                                    <label class="estructura-grado-check cursor-pointer">
                                                                        <input
                                                                            type="checkbox"
                                                                            name="grados[]"
                                                                            value="{{ $grado->id }}"
                                                                            class="rounded border-slate-300 text-slate-900 focus:ring-slate-800"
                                                                            x-model="grados"
                                                                        >
                                                                        <span class="text-sm font-medium text-slate-800">{{ $grado->nombre }}</span>
                                                                    </label>
                                                                    <input
                                                                        id="nombre-grado-{{ $grado->id }}"
                                                                        type="text"
                                                                        name="nombre_grados[{{ $grado->id }}]"
                                                                        value="{{ $nombresGrados[(string) $grado->id] ?? '' }}"
                                                                        maxlength="150"
                                                                        placeholder="Nombre en el establecimiento"
                                                                        aria-label="Nombre en el establecimiento de {{ $grado->nombre }}"
                                                                        class="estructura-grado-nombre rounded-md border-slate-300 text-sm"
                                                                        x-cloak
                                                                        x-show="grados.includes(String({{ $grado->id }}))"
                                                                    >
                                                                </div>
                                                                <x-input-error class="mt-1" :messages="$errors->get('nombre_grados.'.$grado->id)" />
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </section>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-primary-button>Guardar estructura</x-primary-button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</x-app-layout>
