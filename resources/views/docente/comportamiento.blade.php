<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Comportamiento</h2>
            <p class="mt-1 text-sm text-slate-500">Elige el aula, la asignatura y el ciclo. Luego califica cada indicador con la escala del periodo.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'comportamiento-registrado')
                <p class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">Comportamiento guardado.</p>
            @endif

            @if ($aulas === [])
                <p class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center text-slate-500">
                    No tienes aulas asignadas en un periodo activo.
                </p>
            @else
                <x-docente-contexto
                    :aulas="$aulas"
                    :seleccion="$seleccion"
                    :store-route="route('Docente.comportamiento.store')"
                    destino="comportamiento"
                    :requiere-parcial="false"
                >
                    <div class="mt-4 flex flex-col gap-4 rounded-lg bg-white p-6 shadow-sm" x-show="seleccionCompleta && coincideConGuardado" x-cloak>
                        @if ($registro && $contexto)
                            <p class="text-sm leading-6 text-slate-600">
                                {{ $contexto->etiqueta() }}. Cada indicador usa la tabla de comportamiento del periodo.
                                Esta calificación no modifica el promedio de las asignaturas.
                            </p>

                            <x-input-error :messages="$errors->get('comportamientos')" />

                            @if ($registro['escala'] === [])
                                <p class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    El periodo no tiene una tabla de comportamiento activa. El administrador la configura en Tablas de Equivalencias.
                                </p>
                            @endif

                            @if ($registro['indicadores'] === [])
                                <p class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    El periodo no tiene indicadores de comportamiento. El administrador los elige en Criterios de comportamiento.
                                </p>
                            @endif

                            @if ($registro['escala'] !== [] && $registro['indicadores'] !== [] && $registro['alumnos'] === [])
                                <p class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                                    No hay alumnos matriculados en este aula.
                                </p>
                            @endif

                            @if ($registro['escala'] !== [] && $registro['indicadores'] !== [] && $registro['alumnos'] !== [])
                                <form method="POST" action="{{ route('Docente.comportamiento.registrar') }}" class="flex flex-col gap-4">
                                    @csrf
                                    <input type="hidden" name="establecimiento_aula_id" value="{{ $contexto->aula->id }}">
                                    <input type="hidden" name="establecimiento_asignatura_id" value="{{ $contexto->asignatura->id }}">
                                    <input type="hidden" name="establecimiento_periodo_ciclo_id" value="{{ $contexto->ciclo->id }}">

                                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead class="bg-slate-50">
                                                <tr>
                                                    <th scope="col" class="sticky left-0 z-10 w-12 bg-slate-50 px-4 py-3 text-left font-semibold text-slate-700">Nº</th>
                                                    <th scope="col" class="sticky left-12 z-10 bg-slate-50 px-4 py-3 text-left font-semibold text-slate-700">Estudiante</th>
                                                    @foreach ($registro['indicadores'] as $indicador)
                                                        <th scope="col" class="min-w-56 px-4 py-3 text-left font-semibold text-slate-700" title="{{ $indicador['descripcion'] }}">
                                                            <span class="block text-xs font-semibold uppercase tracking-wide text-indigo-700">{{ $indicador['codigo'] }}</span>
                                                            <span class="mt-1 block font-medium normal-case tracking-normal text-slate-700">{{ $indicador['descripcion'] }}</span>
                                                        </th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach ($registro['alumnos'] as $indice => $alumno)
                                                    <tr>
                                                        <td class="sticky left-0 bg-white px-4 py-2 text-slate-500">{{ $indice + 1 }}</td>
                                                        <td class="sticky left-12 bg-white px-4 py-2 text-slate-800">{{ $alumno['nombre'] }}</td>
                                                        @foreach ($registro['indicadores'] as $indicador)
                                                            @php
                                                                $celda = $alumno['indicadores'][$indicador['id']] ?? ['calificacion' => null, 'observacion' => null];
                                                                $calificacionActual = old('comportamientos.'.$alumno['id'].'.'.$indicador['id'].'.calificacion', $celda['calificacion']);
                                                                $observacionActual = old('comportamientos.'.$alumno['id'].'.'.$indicador['id'].'.observacion', $celda['observacion']);
                                                            @endphp
                                                            <td class="px-4 py-2 align-top" x-data="{ calificacion: @js($calificacionActual ?? ''), exige: @js($registro['exige']) }">
                                                                <label class="sr-only" for="calificacion-{{ $alumno['id'] }}-{{ $indicador['id'] }}">{{ $indicador['codigo'] }} de {{ $alumno['nombre'] }}</label>
                                                                <select
                                                                    id="calificacion-{{ $alumno['id'] }}-{{ $indicador['id'] }}"
                                                                    name="comportamientos[{{ $alumno['id'] }}][{{ $indicador['id'] }}][calificacion]"
                                                                    x-model="calificacion"
                                                                    class="block w-full min-w-52 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                                    required
                                                                >
                                                                    <option value="">Calificación</option>
                                                                    @foreach ($registro['escala'] as $escala)
                                                                        <option value="{{ $escala['id'] }}">{{ $escala['codigo'] }} — {{ $escala['nombre'] }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <x-input-error class="mt-1" :messages="$errors->get('comportamientos.'.$alumno['id'].'.'.$indicador['id'].'.calificacion')" />
                                                                <div class="mt-2" x-show="exige.includes(String(calificacion))" x-cloak>
                                                                    <label class="sr-only" for="observacion-{{ $alumno['id'] }}-{{ $indicador['id'] }}">Observación de {{ $indicador['codigo'] }} para {{ $alumno['nombre'] }}</label>
                                                                    <textarea
                                                                        id="observacion-{{ $alumno['id'] }}-{{ $indicador['id'] }}"
                                                                        name="comportamientos[{{ $alumno['id'] }}][{{ $indicador['id'] }}][observacion]"
                                                                        rows="2"
                                                                        maxlength="500"
                                                                        class="block w-full min-w-52 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                                    >{{ $observacionActual }}</textarea>
                                                                    <x-input-error class="mt-1" :messages="$errors->get('comportamientos.'.$alumno['id'].'.'.$indicador['id'].'.observacion')" />
                                                                </div>
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <ul class="flex flex-col gap-1 text-xs leading-5 text-slate-500">
                                        @foreach ($registro['escala'] as $escala)
                                            <li>
                                                @if ($escala['jerarquia'])
                                                    <span class="font-semibold text-slate-700">{{ $escala['jerarquia'] }}.</span>
                                                @endif
                                                <span class="font-semibold text-slate-700">{{ $escala['codigo'] }}.</span>
                                                {{ $escala['descripcion'] ?: $escala['nombre'] }}
                                            </li>
                                        @endforeach
                                    </ul>
                                    <div class="flex justify-end">
                                        <x-primary-button>Guardar comportamiento</x-primary-button>
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
