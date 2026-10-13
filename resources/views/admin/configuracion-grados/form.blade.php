<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">{{ $configuracion ? 'Editar configuración por grados' : 'Nueva configuración por grados' }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $periodo?->nombre ?? 'Sin periodo activo' }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if ($periodo === null || $periodo->ciclos->isEmpty())
                <p class="rounded-2xl border border-amber-200 bg-amber-50 px-6 py-8 text-center text-amber-900">No hay un periodo activo con ciclos configurados. Configura primero el periodo y sus ciclos.</p>
            @else
                @if ($periodo->estaAbierto())
                    <p class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">El periodo está aperturado; esta configuración se muestra en modo de consulta.</p>
                @endif

                <x-input-error class="mb-4" :messages="$errors->all()" />

                <form
                    method="POST"
                    action="{{ $configuracion ? route('Admin.configuracion-grados.update', $configuracion) : route('Admin.configuracion-grados.store') }}"
                    class="flex flex-col gap-6"
                    x-data="{ gradosSeleccionados: @js($gradosSeleccionados), pestana: @js(collect($errors->keys())->contains(fn (string $campo): bool => str_starts_with($campo, 'ciclos.')) ? 'ciclos' : 'periodo') }"
                >
                    @csrf
                    @if ($configuracion)
                        @method('PATCH')
                    @endif

                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">Identificación</h3>
                        <label for="nombre" class="mt-4 block text-sm font-medium text-slate-700">Nombre de la configuración</label>
                        <input id="nombre" name="nombre" type="text" maxlength="120" required value="{{ old('nombre', $configuracion?->nombre) }}" @disabled($periodo->estaAbierto()) class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">Grados</h3>
                        <p class="mt-1 text-sm text-slate-600">Selecciona uno o varios grados sin evaluación por destrezas y con calificación cuantitativa. La configuración de ciclos se aplicará a todos los grados seleccionados.</p>
                        <label for="grados" class="mt-4 block text-sm font-medium text-slate-700">Grados disponibles</label>
                        <select id="grados" name="grados[]" multiple required x-model="gradosSeleccionados" @disabled($periodo->estaAbierto()) class="mt-1 block min-h-36 w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($gradosFormulario as $grado)
                                <option value="{{ $grado['id'] }}" @selected(in_array($grado['id'], $gradosSeleccionados, true))>{{ $grado['nombre'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-slate-500">Usa Ctrl (Windows) o ⌘ (Mac) para seleccionar varios grados.</p>
                    </section>

                    <nav class="flex flex-wrap gap-2 border-b border-slate-200" role="tablist" aria-label="Configuración por grados">
                        <button type="button" role="tab" :aria-selected="pestana === 'periodo'" @click="pestana = 'periodo'" :class="pestana === 'periodo' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-600 hover:text-slate-900'" class="border-b-2 px-4 py-3 text-sm font-semibold">Configuración del periodo</button>
                        <button type="button" role="tab" :aria-selected="pestana === 'ciclos'" @click="pestana = 'ciclos'" :class="pestana === 'ciclos' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-600 hover:text-slate-900'" class="border-b-2 px-4 py-3 text-sm font-semibold">Configuración de ciclos</button>
                    </nav>

                    <section x-show="pestana === 'periodo'" role="tabpanel" class="flex flex-col gap-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-slate-900">Ponderación de los ciclos</h3>
                            <p class="mt-1 text-sm text-slate-600">Define cuánto aporta cada ciclo al periodo para los grados seleccionados.</p>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                @foreach ($ciclosFormulario as $ciclo)
                                    <div>
                                        <label for="ponderacion-periodo-{{ $ciclo['orden'] }}" class="block text-sm font-medium text-slate-700">{{ $ciclo['nombre'] }}</label>
                                        <div class="mt-1 flex items-center gap-2">
                                            <input id="ponderacion-periodo-{{ $ciclo['orden'] }}" name="ponderaciones_periodo[{{ $ciclo['orden'] }}]" type="number" min="0" max="100" step="0.01" required value="{{ $ciclo['porcentaje_periodo'] }}" @disabled($periodo->estaAbierto()) class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <span class="text-sm text-slate-500">%</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('ponderaciones_periodo')" />
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-slate-900">Ponderación final</h3>
                            <p class="mt-1 text-sm text-slate-600">Define el peso del examen y/o proyecto final. Si no aplica, deja el valor en 0%. Los ciclos y la ponderación final deben sumar 100%.</p>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="porcentaje-examen-final" class="block text-sm font-medium text-slate-700">Examen final</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <input id="porcentaje-examen-final" name="porcentaje_examen_final" type="number" min="0" max="100" step="0.01" required value="{{ $porcentajeExamenFinal }}" @disabled($periodo->estaAbierto()) class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <span class="text-sm text-slate-500">%</span>
                                    </div>
                                </div>
                                <div>
                                    <label for="porcentaje-proyecto-final" class="block text-sm font-medium text-slate-700">Proyecto final</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <input id="porcentaje-proyecto-final" name="porcentaje_proyecto_final" type="number" min="0" max="100" step="0.01" required value="{{ $porcentajeProyectoFinal }}" @disabled($periodo->estaAbierto()) class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <span class="text-sm text-slate-500">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section x-show="pestana === 'ciclos'" role="tabpanel" x-cloak class="flex flex-col gap-4">
                        @foreach ($ciclosFormulario as $ciclo)
                            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                                <h3 class="text-lg font-semibold text-slate-900">Ciclo {{ $loop->iteration }} · {{ $ciclo['nombre'] }}</h3>
                                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                                    @foreach ([
                                        'porcentaje_insumos' => 'Porcentaje de insumos',
                                        'porcentaje_examen' => 'Porcentaje de examen',
                                        'porcentaje_proyecto' => 'Porcentaje de proyecto',
                                    ] as $campo => $etiqueta)
                                        <div>
                                            <label for="{{ $campo }}-{{ $ciclo['orden'] }}" class="block text-sm font-medium text-slate-700">{{ $etiqueta }}</label>
                                            <div class="mt-1 flex items-center gap-2">
                                                <input id="{{ $campo }}-{{ $ciclo['orden'] }}" name="ciclos[{{ $ciclo['orden'] }}][{{ $campo }}]" type="number" min="0" max="100" step="0.01" required value="{{ $ciclo[$campo] }}" @disabled($periodo->estaAbierto()) class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <span class="text-sm text-slate-500">%</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <x-input-error class="mt-2" :messages="$errors->get('ciclos.'.$ciclo['orden'])" />
                                <p class="mt-4 text-sm text-slate-500">Insumos + examen + proyecto deben sumar 100% para este ciclo.</p>
                            </div>
                        @endforeach
                    </section>

                    <section class="rounded-xl border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900">
                        Ambas pestañas aplican a todos los grados seleccionados. La ponderación de los ciclos más la ponderación final debe sumar 100%; dentro de cada ciclo, insumos, examen y proyecto también deben sumar 100%.
                    </section>

                    <div class="flex flex-wrap gap-3">
                        @if (! $periodo->estaAbierto())
                            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Guardar configuración</button>
                        @endif
                        <a href="{{ route('Admin.configuracion-grados.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Volver al listado</a>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
