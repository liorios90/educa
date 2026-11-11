<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Destrezas</h2>
            <p class="mt-1 text-sm text-slate-500">
                @if (($registro['usa_ambitos'] ?? false) === true)
                    Elige el aula, el ámbito y el periodo. En Inicial y Preparatoria se califica cada destreza, sin promedio.
                @else
                    Elige el aula, la asignatura y el periodo. Califica cada indicador con A, EP, I o NE.
                @endif
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'destrezas-registradas')
                <p class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Destrezas guardadas.</p>
            @endif

            @if ($aulas === [])
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    No tienes aulas asignadas en un periodo activo.
                </p>
            @else
                <x-docente-contexto
                    :aulas="$aulas"
                    :seleccion="$seleccion"
                    :store-route="route('Docente.destrezas.store')"
                    destino="destrezas"
                    :requiere-parcial="false"
                >
                    <div class="mt-4 flex flex-col gap-4 rounded-lg bg-white p-6 shadow-sm" x-show="seleccionCompleta && coincideConGuardado" x-cloak>
                        @if ($registro && $contexto)
                            <p class="text-sm leading-6 text-slate-600">
                                {{ $contexto->etiqueta() }}.
                                @if ($registro['usa_ambitos'])
                                    Marca los indicadores que observaste. El conteo te ayuda a elegir la letra de cada destreza, de A+ a E-, o NE si no se trabajó. No se promedia una nota.
                                @elseif ($registro['equivale'])
                                    Escala de Básica Elemental: A alcanzado, EP en proceso, I iniciado y NE no evaluado.
                                    En la libreta también se muestra la nota sobre 10 equivalente.
                                @else
                                    Escala cualitativa: A alcanzado, EP en proceso, I iniciado y NE no evaluado. No se promedia una nota sobre 10.
                                @endif
                            </p>

                            <x-input-error :messages="$errors->get('destrezas')" />

                            @php
                                $porAmbito = (bool) $registro['usa_ambitos'];
                                $puedeCalificar = $porAmbito
                                    ? $registro['destrezas'] !== []
                                    : collect($registro['destrezas'])->contains(
                                        fn (array $destreza): bool => $destreza['indicadores'] !== [],
                                    );
                            @endphp

                            @if (! $puedeCalificar)
                                <p class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    {{ $porAmbito ? 'Este ámbito no tiene destrezas para el grado. Sistemas las carga en Áreas y asignaturas.' : 'Esta asignatura no tiene destrezas ni indicadores para el grado. Sistemas los carga en Áreas y asignaturas.' }}
                                </p>
                            @endif

                            @if ($puedeCalificar && $registro['alumnos'] === [])
                                <p class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                                    No hay alumnos matriculados en este aula.
                                </p>
                            @endif

                            @if ($puedeCalificar && $registro['alumnos'] !== [])
                                <form method="POST" action="{{ route('Docente.destrezas.registrar') }}" class="flex flex-col gap-4">
                                    @csrf
                                    <input type="hidden" name="establecimiento_aula_id" value="{{ $contexto->aula->id }}">
                                    <input type="hidden" name="establecimiento_asignatura_id" value="{{ $contexto->asignatura->id }}">
                                    <input type="hidden" name="establecimiento_periodo_ciclo_id" value="{{ $contexto->ciclo->id }}">

                                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead class="bg-slate-50">
                                                <tr>
                                                    <th scope="col" class="sticky left-0 z-10 bg-slate-50 px-4 py-3" colspan="2"></th>
                                                    @foreach ($registro['destrezas'] as $destreza)
                                                        @if ($porAmbito || $destreza['indicadores'] !== [])
                                                            <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-800" colspan="{{ $porAmbito ? 1 : count($destreza['indicadores']) }}">
                                                                @if ($porAmbito && $destreza['criterio'])
                                                                    <span class="block text-xs font-semibold uppercase tracking-wide text-indigo-700">Criterio {{ $destreza['criterio']['codigo'] }}</span>
                                                                @endif
                                                                <span class="block text-xs font-semibold uppercase tracking-wide text-indigo-700">{{ $destreza['codigo'] }}</span>
                                                                <span class="mt-1 block font-medium normal-case tracking-normal">{{ $destreza['descripcion'] }}</span>
                                                            </th>
                                                        @endif
                                                    @endforeach
                                                </tr>
                                                <tr>
                                                    <th scope="col" class="sticky left-0 z-10 w-12 bg-slate-50 px-4 py-3 text-left font-semibold text-slate-700">Nº</th>
                                                    <th scope="col" class="sticky left-12 z-10 bg-slate-50 px-4 py-3 text-left font-semibold text-slate-700">Estudiante</th>
                                                    @foreach ($registro['destrezas'] as $destreza)
                                                        @if ($porAmbito)
                                                            <th scope="col" class="min-w-56 px-4 py-3 text-left font-semibold text-slate-700">
                                                                <span class="block font-medium normal-case tracking-normal text-slate-700">Calificación</span>
                                                            </th>
                                                        @else
                                                            @foreach ($destreza['indicadores'] as $indicador)
                                                                <th scope="col" class="min-w-56 px-4 py-3 text-left font-semibold text-slate-700">
                                                                    <span class="block text-xs font-semibold uppercase tracking-wide text-indigo-700">{{ $indicador['codigo'] }}</span>
                                                                    <span class="mt-1 block font-medium normal-case tracking-normal text-slate-700">{{ $indicador['descripcion'] }}</span>
                                                                </th>
                                                            @endforeach
                                                        @endif
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach ($registro['alumnos'] as $indice => $alumno)
                                                    <tr>
                                                        <td class="sticky left-0 bg-white px-4 py-2 text-slate-500">{{ $indice + 1 }}</td>
                                                        <td class="sticky left-12 bg-white px-4 py-2 text-slate-800">{{ $alumno['nombre'] }}</td>
                                                        @foreach ($registro['destrezas'] as $destreza)
                                                            @if ($porAmbito)
                                                                @php
                                                                    $celda = $alumno['calificaciones'][$destreza['id']] ?? ['calificacion' => null, 'observacion' => null, 'indicadores' => []];
                                                                    $calificacionActual = old('destrezas.'.$alumno['id'].'.'.$destreza['id'].'.calificacion', $celda['calificacion']);
                                                                    $observacionActual = old('destrezas.'.$alumno['id'].'.'.$destreza['id'].'.observacion', $celda['observacion']);
                                                                    $seleccionados = old('destrezas.'.$alumno['id'].'.'.$destreza['id'].'.indicadores', $celda['indicadores'] ?? []);
                                                                    $campo = $destreza['id'];
                                                                    $etiquetaCampo = $destreza['codigo'];
                                                                @endphp
                                                            @endif
                                                            @foreach ($porAmbito ? [0] : $destreza['indicadores'] as $indicador)
                                                                @unless ($porAmbito)
                                                                    @php
                                                                        $celda = $alumno['indicadores'][$indicador['id']] ?? ['calificacion' => null, 'observacion' => null];
                                                                        $calificacionActual = old('destrezas.'.$alumno['id'].'.'.$indicador['id'].'.calificacion', $celda['calificacion']);
                                                                        $observacionActual = old('destrezas.'.$alumno['id'].'.'.$indicador['id'].'.observacion', $celda['observacion']);
                                                                        $campo = $indicador['id'];
                                                                        $etiquetaCampo = $indicador['codigo'];
                                                                    @endphp
                                                                @endunless
                                                                <td class="px-4 py-2 align-top">
                                                                    @if ($porAmbito && $destreza['indicadores'] !== [])
                                                                        @include('docente._indicadores-observados', [
                                                                            'alumnoId' => $alumno['id'],
                                                                            'destreza' => $destreza,
                                                                            'seleccionados' => $seleccionados,
                                                                        ])
                                                                    @endif
                                                                    <label class="sr-only" for="calificacion-{{ $alumno['id'] }}-{{ $campo }}">{{ $etiquetaCampo }} de {{ $alumno['nombre'] }}</label>
                                                                    <select
                                                                        id="calificacion-{{ $alumno['id'] }}-{{ $campo }}"
                                                                        name="destrezas[{{ $alumno['id'] }}][{{ $campo }}][calificacion]"
                                                                        class="block w-full min-w-52 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                                        required
                                                                    >
                                                                        <option value="">Calificación</option>
                                                                        @foreach ($registro['escala'] as $escala)
                                                                            <option value="{{ $escala['codigo'] }}" @selected($calificacionActual === $escala['codigo'])>{{ $escala['codigo'] }} — {{ $escala['etiqueta'] }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <x-input-error class="mt-1" :messages="$errors->get('destrezas.'.$alumno['id'].'.'.$campo.'.calificacion')" />
                                                                    <label class="sr-only" for="observacion-{{ $alumno['id'] }}-{{ $campo }}">Recomendación de {{ $etiquetaCampo }} para {{ $alumno['nombre'] }}</label>
                                                                    <textarea
                                                                        id="observacion-{{ $alumno['id'] }}-{{ $campo }}"
                                                                        name="destrezas[{{ $alumno['id'] }}][{{ $campo }}][observacion]"
                                                                        rows="2"
                                                                        maxlength="500"
                                                                        placeholder="Recomendación"
                                                                        class="mt-2 block w-full min-w-52 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                                    >{{ $observacionActual }}</textarea>
                                                                    <x-input-error class="mt-1" :messages="$errors->get('destrezas.'.$alumno['id'].'.'.$campo.'.observacion')" />
                                                                </td>
                                                            @endforeach
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <ul class="flex flex-col gap-1 text-xs leading-5 text-slate-500">
                                        @foreach ($registro['escala'] as $escala)
                                            <li><span class="font-semibold text-slate-700">{{ $escala['codigo'] }}.</span> {{ $escala['descripcion'] }}</li>
                                        @endforeach
                                    </ul>
                                    <div class="flex justify-end">
                                        <x-primary-button>Guardar destrezas</x-primary-button>
                                    </div>
                                </form>
                            @endif
                        @endif
                    </div>
                </x-docente-contexto>
            @endif
        </div>
    </div>
</x-app-layout>
