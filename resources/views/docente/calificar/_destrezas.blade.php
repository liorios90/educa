@php
    $esAmbito = (bool) ($registroDestrezas['usa_ambitos'] ?? false);
    $tieneIndicadores = $esAmbito
        ? ($registroDestrezas['destrezas'] ?? []) !== []
        : collect($registroDestrezas['destrezas'])->contains(
            fn (array $destreza): bool => $destreza['indicadores'] !== [],
        );
@endphp

<div x-show="coincideConGuardado">
    <p class="text-sm text-slate-600">
            @if ($esAmbito)
                Califica cada destreza del ámbito. No se saca promedio.
            @else
                Califica cada indicador con la escala A, EP, I o NE. En Básica Elemental esa escala también produce la nota sobre 10.
            @endif
    </p>

    @if (! $tieneIndicadores)
        <p class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            @if ($esAmbito)
                Este ámbito todavía no tiene destrezas. El catálogo se mantiene en Áreas y asignaturas.
            @else
                Esta asignatura todavía no tiene indicadores de evaluación. El administrador los registra en Asignaturas, con la casilla Califica por destrezas.
            @endif
        </p>
    @elseif ($registroDestrezas['alumnos'] === [])
        <p class="mt-4 rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
            No hay alumnos matriculados en este aula.
        </p>
    @else
        <form method="POST" action="{{ route('Docente.calificar.destrezas.store') }}" class="mt-4 flex flex-col gap-4">
            @csrf
            <input type="hidden" name="establecimiento_aula_id" value="{{ $contexto->aula->id }}">
            <input type="hidden" name="establecimiento_asignatura_id" value="{{ $contexto->asignatura->id }}">
            <input type="hidden" name="establecimiento_periodo_ciclo_id" value="{{ $contexto->ciclo->id }}">
            <input type="hidden" name="parcial_numero" value="{{ $contexto->parcialNumero }}">

            @if ($esAmbito)
                <p class="text-sm font-semibold text-slate-800">Ámbito: {{ $contexto->asignatura->nombre() }}</p>
                <p class="text-sm text-slate-600">Marca los indicadores que observaste. El conteo te ayuda a elegir la letra de la destreza. A+ y A- es logrado; de B+ a C- va en proceso; de D+ a E- está iniciado. NE significa que no se trabajó. No se promedia.</p>
                @foreach ($registroDestrezas['destrezas'] as $destreza)
                    <section class="rounded-lg border border-slate-200">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                            @if ($destreza['criterio'])
                                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Criterio {{ $destreza['criterio']['codigo'] }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $destreza['criterio']['descripcion'] }}</p>
                            @endif
                            <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Destreza {{ $destreza['codigo'] }} · {{ $destreza['imprescindible'] ? 'Imprescindible' : 'Deseable' }}</p>
                            <p class="mt-1 text-sm font-medium text-slate-800">{{ $destreza['descripcion'] }}</p>
                        </div>
                        <div class="overflow-x-auto px-4 py-3">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead>
                                    <tr>
                                        <th scope="col" class="w-12 px-2 py-2 text-left font-semibold text-slate-700">Nº</th>
                                        <th scope="col" class="px-2 py-2 text-left font-semibold text-slate-700">Estudiante</th>
                                        <th scope="col" class="w-80 px-2 py-2 text-left font-semibold text-slate-700">Calificación</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($registroDestrezas['alumnos'] as $indice => $alumno)
                                        @php
                                            $celda = $alumno['calificaciones'][$destreza['id']] ?? ['calificacion' => null, 'observacion' => null, 'indicadores' => []];
                                            $calificacionActual = old('destrezas.'.$alumno['id'].'.'.$destreza['id'].'.calificacion', $celda['calificacion']);
                                            $seleccionados = old('destrezas.'.$alumno['id'].'.'.$destreza['id'].'.indicadores', $celda['indicadores'] ?? []);
                                        @endphp
                                        <tr>
                                            <td class="px-2 py-2 text-slate-500">{{ $indice + 1 }}</td>
                                            <td class="px-2 py-2 text-slate-800">{{ $alumno['nombre'] }}</td>
                                            <td class="px-2 py-2">
                                                @if ($destreza['indicadores'] !== [])
                                                    @include('docente._indicadores-observados', [
                                                        'alumnoId' => $alumno['id'],
                                                        'destreza' => $destreza,
                                                        'seleccionados' => $seleccionados,
                                                    ])
                                                @endif
                                                <label class="sr-only" for="calificacion-{{ $alumno['id'] }}-{{ $destreza['id'] }}">{{ $destreza['codigo'] }} de {{ $alumno['nombre'] }}</label>
                                                <select
                                                    id="calificacion-{{ $alumno['id'] }}-{{ $destreza['id'] }}"
                                                    name="destrezas[{{ $alumno['id'] }}][{{ $destreza['id'] }}][calificacion]"
                                                    class="block w-full rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    required
                                                >
                                                    <option value="">Selecciona</option>
                                                    @foreach ($registroDestrezas['escala'] as $escala)
                                                        <option value="{{ $escala['codigo'] }}" @selected($calificacionActual === $escala['codigo'])>{{ $escala['codigo'] }} — {{ $escala['etiqueta'] }}</option>
                                                    @endforeach
                                                </select>
                                                <x-input-error class="mt-1" :messages="$errors->get('destrezas.'.$alumno['id'].'.'.$destreza['id'].'.calificacion')" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endforeach
            @else
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="sticky left-0 z-10 bg-slate-50 px-4 py-3" colspan="2"></th>
                            @foreach ($registroDestrezas['destrezas'] as $destreza)
                                @if ($destreza['indicadores'] !== [])
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-800" colspan="{{ count($destreza['indicadores']) }}">
                                        <span class="block text-xs font-semibold uppercase tracking-wide text-indigo-700">{{ $destreza['codigo'] }}</span>
                                        <span class="mt-1 block font-medium normal-case tracking-normal">{{ $destreza['descripcion'] }}</span>
                                    </th>
                                @endif
                            @endforeach
                        </tr>
                        <tr>
                            <th scope="col" class="sticky left-0 z-10 w-12 bg-slate-50 px-4 py-3 text-left font-semibold text-slate-700">Nº</th>
                            <th scope="col" class="sticky left-12 z-10 bg-slate-50 px-4 py-3 text-left font-semibold text-slate-700">Estudiante</th>
                            @foreach ($registroDestrezas['destrezas'] as $destreza)
                                @foreach ($destreza['indicadores'] as $indicador)
                                    <th scope="col" class="min-w-56 px-4 py-3 text-left font-semibold text-slate-700">
                                        <span class="block text-xs font-semibold uppercase tracking-wide text-indigo-700">{{ $indicador['codigo'] }}</span>
                                        <span class="mt-1 block font-medium normal-case tracking-normal text-slate-700">{{ $indicador['descripcion'] }}</span>
                                    </th>
                                @endforeach
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($registroDestrezas['alumnos'] as $indice => $alumno)
                            <tr>
                                <td class="sticky left-0 bg-white px-4 py-2 text-slate-500">{{ $indice + 1 }}</td>
                                <td class="sticky left-12 bg-white px-4 py-2 text-slate-800">{{ $alumno['nombre'] }}</td>
                                @foreach ($registroDestrezas['destrezas'] as $destreza)
                                    @foreach ($destreza['indicadores'] as $indicador)
                                        @php
                                            $celda = $alumno['indicadores'][$indicador['id']] ?? ['calificacion' => null, 'observacion' => null];
                                            $calificacionActual = old('destrezas.'.$alumno['id'].'.'.$indicador['id'].'.calificacion', $celda['calificacion']);
                                        @endphp
                                        <td class="px-4 py-2 align-top">
                                            <label class="sr-only" for="calificacion-{{ $alumno['id'] }}-{{ $indicador['id'] }}">{{ $indicador['codigo'] }} de {{ $alumno['nombre'] }}</label>
                                            <select
                                                id="calificacion-{{ $alumno['id'] }}-{{ $indicador['id'] }}"
                                                name="destrezas[{{ $alumno['id'] }}][{{ $indicador['id'] }}][calificacion]"
                                                class="block w-full min-w-52 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                required
                                            >
                                                <option value="">Selecciona</option>
                                                @foreach ($registroDestrezas['escala'] as $escala)
                                                    <option value="{{ $escala['codigo'] }}" @selected($calificacionActual === $escala['codigo'])>{{ $escala['codigo'] }} — {{ $escala['etiqueta'] }}</option>
                                                @endforeach
                                            </select>
                                            <x-input-error class="mt-1" :messages="$errors->get('destrezas.'.$alumno['id'].'.'.$indicador['id'].'.calificacion')" />
                                        </td>
                                    @endforeach
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <ul class="flex flex-col gap-1 text-xs leading-5 text-slate-500">
                @foreach ($registroDestrezas['escala'] as $escala)
                    <li><span class="font-semibold text-slate-700">{{ $escala['codigo'] }}.</span> {{ $escala['descripcion'] }}</li>
                @endforeach
            </ul>

            <div class="flex justify-end">
                <x-primary-button>Grabar calificaciones</x-primary-button>
            </div>
        </form>
    @endif
</div>
