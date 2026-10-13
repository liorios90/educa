<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Proceso de Cierres</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $periodo?->nombre ?? 'Sin periodo activo' }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8" x-data="{ pestana: 'parciales' }">
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
            </style>

            @if (session('status') === 'cierres-parciales-updated')
                <p class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">Se guardó la configuración de cierre de parciales.</p>
            @endif

            <x-input-error class="mb-4" :messages="$errors->all()" />

            <nav class="estructura-folders" role="tablist" aria-label="Proceso de cierres">
                <button type="button" role="tab" class="estructura-folder" :class="{ 'is-active': pestana === 'parciales' }" :aria-selected="pestana === 'parciales'" @click="pestana = 'parciales'">
                    <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                    </svg>
                    <span>Parciales</span>
                </button>
                <button type="button" role="tab" class="estructura-folder" :class="{ 'is-active': pestana === 'esquema' }" :aria-selected="pestana === 'esquema'" @click="pestana = 'esquema'">
                    <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                    </svg>
                    <span>{{ $nombreEsquema }}</span>
                </button>
                <button type="button" role="tab" class="estructura-folder" :class="{ 'is-active': pestana === 'periodo' }" :aria-selected="pestana === 'periodo'" @click="pestana = 'periodo'">
                    <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                    </svg>
                    <span>Periodo</span>
                </button>
            </nav>

            <section x-show="pestana === 'parciales'" role="tabpanel" class="estructura-folder-panel">
                <header class="border-b border-slate-100 px-5 py-4">
                    <h3 class="estructura-nivel-title">Parciales</h3>
                </header>
                <div class="estructura-subniveles">
                    @if ($periodo === null)
                        <p class="estructura-subnivel text-sm text-amber-900">Selecciona una oferta y un periodo activo para consultar sus parciales.</p>
                    @elseif (! $periodo->estaAbierto())
                        <p class="estructura-subnivel text-sm text-amber-900">Apertura el periodo para generar los parciales y habilitar su cierre.</p>
                    @elseif ($parciales->isEmpty())
                        <p class="estructura-subnivel text-sm text-slate-600">El periodo no tiene parciales configurados.</p>
                    @else
                        <form method="POST" action="{{ route('Admin.proceso-cierres.update') }}" class="flex flex-col gap-4">
                            @csrf
                            @method('PUT')
                            @foreach ($ciclosFormulario as $ciclo)
                                <section class="rounded-xl border border-slate-200 bg-white p-5">
                                    <div>
                                        <h4 class="text-base font-semibold text-slate-900">Ciclo {{ $loop->iteration }}</h4>
                                        <p class="mt-1 text-sm text-slate-600">{{ $ciclo['nombre'] }}</p>
                                    </div>
                                    <div class="mt-4 flex flex-col gap-3">
                                        @foreach ($ciclo['parciales'] as $parcial)
                                            <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3">
                                                <span class="text-sm font-medium text-slate-800">Parcial {{ $parcial['numero'] }}</span>
                                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                                    <input
                                                        type="checkbox"
                                                        name="parciales_cerrados[]"
                                                        value="{{ $parcial['id'] }}"
                                                        aria-label="Cerrar parcial {{ $parcial['numero'] }} de {{ $ciclo['nombre'] }}"
                                                        @checked(in_array((string) $parcial['id'], array_map('strval', $parcialesSeleccionados), true))
                                                        class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                    >
                                                    Cerrar parcial
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                            <div>
                                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Guardar</button>
                            </div>
                        </form>
                    @endif
                </div>
            </section>

            <section x-show="pestana === 'esquema'" x-cloak role="tabpanel" class="estructura-folder-panel">
                <header class="border-b border-slate-100 px-5 py-4">
                    <h3 class="estructura-nivel-title">{{ $nombreEsquema }}</h3>
                </header>
                <div class="estructura-subniveles">
                    <p class="estructura-subnivel text-center text-sm text-slate-600">En construcción.</p>
                </div>
            </section>

            <section x-show="pestana === 'periodo'" x-cloak role="tabpanel" class="estructura-folder-panel">
                <header class="border-b border-slate-100 px-5 py-4">
                    <h3 class="estructura-nivel-title">Periodo</h3>
                </header>
                <div class="estructura-subniveles">
                    <p class="estructura-subnivel text-center text-sm text-slate-600">En construcción.</p>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
