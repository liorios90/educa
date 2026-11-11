@foreach ($subniveles as $subnivel)
    @continue(! $subnivel->califica_por_destrezas)
    @foreach ($subnivel->areas as $area)
        @foreach ($area->asignaturas as $asignatura)
            @foreach ($gradosPorSubnivel[$subnivel->id] ?? [] as $grado)
                @php
                    $claveDestrezas = $asignatura->id.'-'.$grado->id;
                    $destrezasDelGrado = $asignatura->destrezas
                        ->where('grado_id', $grado->id)
                        ->where('activo', true)
                        ->sortBy([['orden', 'asc'], ['id', 'asc']]);
                    $crearBag = $errors->getBag('destreza-create-'.$asignatura->id);
                @endphp
                <div
                    x-show="registroDestrezas === '{{ $claveDestrezas }}'"
                    x-cloak
                    class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/60 p-4"
                    @keydown.escape.window="registroDestrezas = null"
                >
                    <div class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl sm:p-6" @click.outside="registroDestrezas = null">
                        <div class="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <h5 class="text-lg font-semibold text-slate-900">{{ $subnivel->usa_ambitos ? 'Ámbito' : 'Indicadores' }} · {{ $asignatura->nombre }}</h5>
                                <p class="mt-1 text-sm text-slate-500">{{ $grado->pivot->nombre ?? $grado->nombre }}. {{ $subnivel->usa_ambitos ? 'En Inicial y Preparatoria se califica cada destreza de A+ a E-, sin promedio.' : 'El docente califica cada indicador con A, EP, I o NE.' }}</p>
                            </div>
                            <button type="button" class="rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100" @click="registroDestrezas = null">Cerrar</button>
                        </div>

                        @if (session('error') && request('destrezas') === $claveDestrezas)
                            <p class="mb-4 text-sm font-medium text-red-700">{{ session('error') }}</p>
                        @endif
                        @if (in_array(session('status'), ['destreza-created', 'destreza-updated', 'destreza-deleted', 'indicador-created', 'indicador-updated', 'indicador-deleted', 'criterio-created', 'criterio-updated', 'criterio-deleted'], true) && request('destrezas') === $claveDestrezas)
                            <p class="mb-4 text-sm font-medium text-green-700">Indicadores actualizados.</p>
                        @endif

                        <div class="flex flex-col gap-4">
                            @if ($subnivel->usa_ambitos)
                                @foreach ($asignatura->criterios->where('grado_id', $grado->id)->where('activo', true) as $criterio)
                                    <p class="text-sm text-slate-600"><span class="font-medium text-indigo-700">{{ $criterio->codigo }}.</span> {{ $criterio->descripcion }}</p>
                                @endforeach
                            @endif
                            @forelse ($destrezasDelGrado as $destreza)
                                @php
                                    $destrezaBag = $errors->getBag('destreza-'.$destreza->id);
                                    $indicadorBag = $errors->getBag('indicador-create-'.$destreza->id);
                                @endphp
                                <article class="rounded-lg border border-slate-200 p-4">
                                    <form method="POST" action="{{ route('Admin.asignaturas.destrezas.update', [$oferta, $asignatura, $destreza]) }}" class="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-end">
                                        @csrf
                                        @method('patch')
                                        <input type="hidden" name="grado_id" value="{{ $grado->id }}">
                                        <div class="md:w-28">
                                            <x-input-label :for="'codigo-destreza-'.$destreza->id" value="Código" />
                                            <x-text-input :id="'codigo-destreza-'.$destreza->id" class="mt-1 block w-full" type="text" name="codigo" :value="$destrezaBag->isNotEmpty() ? old('codigo', $destreza->codigo) : $destreza->codigo" maxlength="20" required />
                                            <x-input-error class="mt-2" :messages="$destrezaBag->get('codigo')" />
                                        </div>
                                        <div class="md:min-w-56 md:flex-1">
                                            <x-input-label :for="'descripcion-destreza-'.$destreza->id" value="Destreza" />
                                            <x-text-input :id="'descripcion-destreza-'.$destreza->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$destrezaBag->isNotEmpty() ? old('descripcion', $destreza->descripcion) : $destreza->descripcion" maxlength="500" required />
                                            <x-input-error class="mt-2" :messages="$destrezaBag->get('descripcion')" />
                                        </div>
                                        <input type="hidden" name="orden" value="{{ $destreza->orden }}">
                                        @if ($subnivel->usa_ambitos)
                                            <div class="md:w-48">
                                                <x-input-label :for="'criterio-destreza-'.$destreza->id" value="Criterio" />
                                                <select id="criterio-destreza-{{ $destreza->id }}" name="criterio_evaluacion_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="">Sin criterio</option>
                                                    @foreach ($asignatura->criterios->where('grado_id', $grado->id) as $criterio)
                                                        <option value="{{ $criterio->id }}" @selected((string) $destreza->criterio_evaluacion_id === (string) $criterio->id)>{{ $criterio->codigo }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                        <x-secondary-button>Guardar destreza</x-secondary-button>
                                    </form>

                                    <form method="POST" action="{{ route('Admin.asignaturas.destrezas.destroy', [$oferta, $asignatura, $destreza]) }}" class="mt-2" onsubmit="return confirm('¿Eliminar esta destreza?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">Eliminar destreza</button>
                                    </form>

                                    <ul class="mt-4 flex flex-col gap-2">
                                        @foreach ($destreza->indicadores->where('activo', true)->sortBy([['orden', 'asc'], ['id', 'asc']]) as $indicador)
                                            <li class="flex items-start justify-between gap-3 rounded-md bg-slate-50 px-3 py-2 text-sm">
                                                <span><span class="font-medium text-slate-700">{{ $indicador->codigo }}.</span> {{ $indicador->descripcion }}</span>
                                                <form method="POST" action="{{ route('Admin.asignaturas.indicadores.destroy', [$oferta, $asignatura, $destreza, $indicador]) }}">
                                                    @csrf
                                                    @method('delete')
                                                    <button type="submit" class="font-medium text-red-600 hover:text-red-500">Quitar</button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>

                                    <form method="POST" action="{{ route('Admin.asignaturas.indicadores.store', [$oferta, $asignatura, $destreza]) }}" class="mt-3 flex flex-col gap-3 md:flex-row md:flex-wrap md:items-end">
                                        @csrf
                                        <input type="hidden" name="grado" value="{{ $grado->id }}">
                                        <div class="md:w-28">
                                            <x-input-label :for="'codigo-indicador-'.$destreza->id" value="Código" />
                                            <x-text-input :id="'codigo-indicador-'.$destreza->id" class="mt-1 block w-full" type="text" name="codigo" :value="$indicadorBag->isNotEmpty() ? old('codigo') : ''" maxlength="20" required />
                                            <x-input-error class="mt-2" :messages="$indicadorBag->get('codigo')" />
                                        </div>
                                        <div class="md:min-w-56 md:flex-1">
                                            <x-input-label :for="'descripcion-indicador-'.$destreza->id" value="Indicador" />
                                            <x-text-input :id="'descripcion-indicador-'.$destreza->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$indicadorBag->isNotEmpty() ? old('descripcion') : ''" maxlength="500" required />
                                            <x-input-error class="mt-2" :messages="$indicadorBag->get('descripcion')" />
                                        </div>
                                        <input type="hidden" name="orden" value="0">
                                        <x-primary-button>Agregar indicador</x-primary-button>
                                    </form>
                                </article>
                            @empty
                                <p class="text-sm text-slate-500">Esta asignatura todavía no tiene destrezas para este grado.</p>
                            @endforelse
                        </div>

                        <form method="POST" action="{{ route('Admin.asignaturas.destrezas.store', [$oferta, $asignatura]) }}" class="mt-4 flex flex-col gap-3 rounded-lg border border-dashed border-slate-300 p-4 md:flex-row md:flex-wrap md:items-end">
                            @csrf
                            <input type="hidden" name="grado_id" value="{{ $grado->id }}">
                            <input type="hidden" name="orden" value="0">
                            <div class="md:w-28">
                                <x-input-label :for="'codigo-nueva-destreza-'.$claveDestrezas" value="Código" />
                                <x-text-input :id="'codigo-nueva-destreza-'.$claveDestrezas" class="mt-1 block w-full" type="text" name="codigo" :value="$crearBag->isNotEmpty() ? old('codigo') : ''" maxlength="20" required />
                                <x-input-error class="mt-2" :messages="$crearBag->get('codigo')" />
                            </div>
                            <div class="md:min-w-56 md:flex-1">
                                <x-input-label :for="'descripcion-nueva-destreza-'.$claveDestrezas" value="Destreza" />
                                <x-text-input :id="'descripcion-nueva-destreza-'.$claveDestrezas" class="mt-1 block w-full" type="text" name="descripcion" :value="$crearBag->isNotEmpty() ? old('descripcion') : ''" maxlength="500" required />
                                <x-input-error class="mt-2" :messages="$crearBag->get('descripcion')" />
                            </div>
                            <x-primary-button>Crear destreza</x-primary-button>
                        </form>
                    </div>
                </div>
            @endforeach
        @endforeach
    @endforeach
@endforeach
