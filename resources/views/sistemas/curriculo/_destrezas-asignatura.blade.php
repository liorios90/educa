@php
    $crearBag = $errors->getBag('destreza-create-'.$asignatura->id);
    $porAmbitos = (bool) $subnivel->usa_ambitos;
@endphp

<tr>
    <td colspan="5" class="bg-slate-50 px-4 py-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $porAmbitos ? 'Criterios, destrezas e indicadores' : 'Destrezas e indicadores de evaluación' }}</p>
        <p class="mt-1 text-sm text-slate-600">
            @if ($porAmbitos)
                El docente califica cada destreza con letras de A+ a E-, o NE si no se trabajó. No se promedia. Los indicadores dicen qué observar.
            @else
                En Básica Elemental el docente califica cada indicador con A, EP, I o NE.
            @endif
        </p>

        @if ($porAmbitos)
            <div class="mt-4 flex flex-col gap-3">
                @foreach ($asignatura->criterios as $criterio)
                    @php $criterioBag = $errors->getBag('criterio-'.$criterio->id); @endphp
                    <form method="POST" action="{{ route('sistemas.curriculo.criterios.update', [$nivel, $subnivel, $area, $asignatura, $criterio]) }}" class="flex flex-col gap-3 rounded-lg border border-indigo-100 bg-white p-3 md:flex-row md:flex-wrap md:items-end">
                        @csrf
                        @method('patch')
                        <input type="hidden" name="grado_id" value="{{ $criterio->grado_id }}">
                        <input type="hidden" name="orden" value="{{ $criterio->orden }}">
                        <div class="md:w-36">
                            <x-input-label :for="'codigo-criterio-'.$criterio->id" value="Criterio" />
                            <x-text-input :id="'codigo-criterio-'.$criterio->id" class="mt-1 block w-full" type="text" name="codigo" :value="$criterioBag->isNotEmpty() ? old('codigo', $criterio->codigo) : $criterio->codigo" maxlength="32" required />
                        </div>
                        <div class="md:min-w-56 md:flex-1">
                            <x-input-label :for="'descripcion-criterio-'.$criterio->id" value="Qué se evalúa" />
                            <x-text-input :id="'descripcion-criterio-'.$criterio->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$criterioBag->isNotEmpty() ? old('descripcion', $criterio->descripcion) : $criterio->descripcion" maxlength="500" required />
                            <x-input-error class="mt-2" :messages="$criterioBag->get('descripcion')" />
                        </div>
                        <x-secondary-button>Guardar criterio</x-secondary-button>
                        <button form="eliminar-criterio-{{ $criterio->id }}" type="submit" class="font-medium text-red-600 hover:text-red-500">Quitar</button>
                    </form>
                    <form id="eliminar-criterio-{{ $criterio->id }}" method="POST" action="{{ route('sistemas.curriculo.criterios.destroy', [$nivel, $subnivel, $area, $asignatura, $criterio]) }}" onsubmit="return confirm('¿Quitar este criterio? Las destrezas se conservan.')">
                        @csrf
                        @method('delete')
                    </form>
                @endforeach

                @php $criterioCrearBag = $errors->getBag('criterio-create-'.$asignatura->id); @endphp
                <form method="POST" action="{{ route('sistemas.curriculo.criterios.store', [$nivel, $subnivel, $area, $asignatura]) }}" class="flex flex-col gap-3 rounded-lg border border-dashed border-indigo-200 bg-white p-3 md:flex-row md:flex-wrap md:items-end">
                    @csrf
                    <div class="md:w-48">
                        <x-input-label :for="'grado-criterio-'.$asignatura->id" value="Grado" />
                        <select id="grado-criterio-{{ $asignatura->id }}" name="grado_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">Selecciona</option>
                            @foreach ($subnivel->grados as $grado)
                                <option value="{{ $grado->id }}" @selected((string) old('grado_id') === (string) $grado->id)>{{ $grado->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:w-36">
                        <x-input-label :for="'codigo-nuevo-criterio-'.$asignatura->id" value="Código" />
                        <x-text-input :id="'codigo-nuevo-criterio-'.$asignatura->id" class="mt-1 block w-full" type="text" name="codigo" :value="$criterioCrearBag->isNotEmpty() ? old('codigo') : ''" maxlength="32" required />
                        <x-input-error class="mt-2" :messages="$criterioCrearBag->get('codigo')" />
                    </div>
                    <div class="md:min-w-56 md:flex-1">
                        <x-input-label :for="'descripcion-nuevo-criterio-'.$asignatura->id" value="Criterio de evaluación" />
                        <x-text-input :id="'descripcion-nuevo-criterio-'.$asignatura->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$criterioCrearBag->isNotEmpty() ? old('descripcion') : ''" maxlength="500" required />
                    </div>
                    <input type="hidden" name="orden" value="0">
                    <x-primary-button>Agregar criterio</x-primary-button>
                </form>
            </div>
        @endif

        <div class="mt-4 flex flex-col gap-4">
            @forelse ($asignatura->destrezas as $destreza)
                @php
                    $destrezaBag = $errors->getBag('destreza-'.$destreza->id);
                    $indicadorBag = $errors->getBag('indicador-create-'.$destreza->id);
                @endphp
                <article class="rounded-lg border border-slate-200 bg-white p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="font-medium text-slate-800">
                                {{ $destreza->codigo }}
                                <span class="font-normal text-slate-500">· {{ $destreza->grado?->nombre }}</span>
                                @if ($porAmbitos)
                                    <span class="font-normal text-slate-500">· {{ $destreza->imprescindible ? 'Imprescindible' : 'Deseable' }}</span>
                                    @if ($destreza->criterio)
                                        <span class="font-normal text-indigo-700">· {{ $destreza->criterio->codigo }}</span>
                                    @endif
                                @endif
                            </p>
                            <p class="mt-1 text-sm text-slate-600">{{ $destreza->descripcion }}</p>
                        </div>
                        <form method="POST" action="{{ route('sistemas.curriculo.destrezas.destroy', [$nivel, $subnivel, $area, $asignatura, $destreza]) }}" onsubmit="return confirm('¿Eliminar esta destreza?')">
                            @csrf
                            @method('delete')
                            <button type="submit" class="font-medium text-red-600 hover:text-red-500">Eliminar</button>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('sistemas.curriculo.destrezas.update', [$nivel, $subnivel, $area, $asignatura, $destreza]) }}" class="mt-3 flex flex-col gap-3 md:flex-row md:flex-wrap md:items-end">
                        @csrf
                        @method('patch')
                        <div class="md:w-48">
                            <x-input-label :for="'grado-destreza-'.$destreza->id" value="Grado" />
                            <select id="grado-destreza-{{ $destreza->id }}" name="grado_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                @foreach ($subnivel->grados as $grado)
                                    <option value="{{ $grado->id }}" @selected((string) old('grado_id', $destreza->grado_id) === (string) $grado->id)>{{ $grado->nombre }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$destrezaBag->get('grado_id')" />
                        </div>
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
                        <div class="md:w-24">
                            <x-input-label :for="'orden-destreza-'.$destreza->id" value="Orden" />
                            <x-text-input :id="'orden-destreza-'.$destreza->id" class="mt-1 block w-full" type="number" name="orden" :value="$destrezaBag->isNotEmpty() ? old('orden', $destreza->orden) : $destreza->orden" min="0" max="999" required />
                        </div>
                        @if ($porAmbitos)
                            <div class="md:w-56">
                                <x-input-label :for="'criterio-destreza-'.$destreza->id" value="Criterio" />
                                <select id="criterio-destreza-{{ $destreza->id }}" name="criterio_evaluacion_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Sin criterio</option>
                                    @foreach ($asignatura->criterios->where('grado_id', $destreza->grado_id) as $criterio)
                                        <option value="{{ $criterio->id }}" @selected((string) old('criterio_evaluacion_id', $destreza->criterio_evaluacion_id) === (string) $criterio->id)>{{ $criterio->codigo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="hidden" name="imprescindible" value="0">
                                <input type="checkbox" name="imprescindible" value="1" class="rounded border-slate-300" @checked(filter_var(old('imprescindible', $destreza->imprescindible), FILTER_VALIDATE_BOOLEAN))>
                                Imprescindible
                            </label>
                        @endif
                        <x-secondary-button>Guardar destreza</x-secondary-button>
                    </form>

                    <ul class="mt-4 flex flex-col gap-2">
                        @foreach ($destreza->indicadores as $indicador)
                            <li class="flex items-start justify-between gap-3 rounded-md bg-slate-50 px-3 py-2 text-sm">
                                <span><span class="font-medium text-slate-700">{{ $indicador->codigo }}.</span> {{ $indicador->descripcion }}</span>
                                <form method="POST" action="{{ route('sistemas.curriculo.indicadores.destroy', [$nivel, $subnivel, $area, $asignatura, $destreza, $indicador]) }}">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="font-medium text-red-600 hover:text-red-500">Quitar</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>

                    <form method="POST" action="{{ route('sistemas.curriculo.indicadores.store', [$nivel, $subnivel, $area, $asignatura, $destreza]) }}" class="mt-3 flex flex-col gap-3 md:flex-row md:flex-wrap md:items-end">
                        @csrf
                        <div class="md:w-28">
                            <x-input-label :for="'codigo-indicador-'.$destreza->id" value="Código" />
                            <x-text-input :id="'codigo-indicador-'.$destreza->id" class="mt-1 block w-full" type="text" name="codigo" :value="$indicadorBag->isNotEmpty() ? old('codigo') : ''" maxlength="20" required />
                            <x-input-error class="mt-2" :messages="$indicadorBag->get('codigo')" />
                        </div>
                        <div class="md:min-w-56 md:flex-1">
                            <x-input-label :for="'descripcion-indicador-'.$destreza->id" value="Indicador de evaluación" />
                            <x-text-input :id="'descripcion-indicador-'.$destreza->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$indicadorBag->isNotEmpty() ? old('descripcion') : ''" maxlength="500" required />
                            <x-input-error class="mt-2" :messages="$indicadorBag->get('descripcion')" />
                        </div>
                        <div class="md:w-24">
                            <x-input-label :for="'orden-indicador-'.$destreza->id" value="Orden" />
                            <x-text-input :id="'orden-indicador-'.$destreza->id" class="mt-1 block w-full" type="number" name="orden" :value="$indicadorBag->isNotEmpty() ? old('orden', 0) : 0" min="0" max="999" required />
                        </div>
                        <x-primary-button>Agregar indicador</x-primary-button>
                    </form>
                </article>
            @empty
                <p class="text-sm text-slate-500">{{ $porAmbitos ? 'Este ámbito aún no tiene destrezas.' : 'Esta asignatura aún no tiene destrezas.' }}</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('sistemas.curriculo.destrezas.store', [$nivel, $subnivel, $area, $asignatura]) }}" class="mt-4 flex flex-col gap-3 rounded-lg border border-dashed border-slate-300 bg-white p-4 md:flex-row md:flex-wrap md:items-end">
            @csrf
            <div class="md:w-48">
                <x-input-label :for="'grado-nueva-destreza-'.$asignatura->id" value="Grado" />
                <select id="grado-nueva-destreza-{{ $asignatura->id }}" name="grado_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    <option value="">Selecciona</option>
                    @foreach ($subnivel->grados as $grado)
                        <option value="{{ $grado->id }}" @selected((string) old('grado_id') === (string) $grado->id)>{{ $grado->nombre }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$crearBag->get('grado_id')" />
            </div>
            <div class="md:w-28">
                <x-input-label :for="'codigo-nueva-destreza-'.$asignatura->id" value="Código" />
                <x-text-input :id="'codigo-nueva-destreza-'.$asignatura->id" class="mt-1 block w-full" type="text" name="codigo" :value="$crearBag->isNotEmpty() ? old('codigo') : ''" maxlength="20" required />
                <x-input-error class="mt-2" :messages="$crearBag->get('codigo')" />
            </div>
            <div class="md:min-w-56 md:flex-1">
                <x-input-label :for="'descripcion-nueva-destreza-'.$asignatura->id" value="Destreza" />
                <x-text-input :id="'descripcion-nueva-destreza-'.$asignatura->id" class="mt-1 block w-full" type="text" name="descripcion" :value="$crearBag->isNotEmpty() ? old('descripcion') : ''" maxlength="500" required />
                <x-input-error class="mt-2" :messages="$crearBag->get('descripcion')" />
            </div>
            <div class="md:w-24">
                <x-input-label :for="'orden-nueva-destreza-'.$asignatura->id" value="Orden" />
                <x-text-input :id="'orden-nueva-destreza-'.$asignatura->id" class="mt-1 block w-full" type="number" name="orden" :value="$crearBag->isNotEmpty() ? old('orden', 0) : 0" min="0" max="999" required />
            </div>
            <x-primary-button>Agregar destreza</x-primary-button>
        </form>
    </td>
</tr>
