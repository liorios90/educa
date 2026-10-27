<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Comportamiento</h2>
            <p class="mt-1 text-sm text-slate-500">Evaluación cualitativa del quimestre o trimestre, según la convivencia del aula.</p>
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
                    :requiere-asignatura="false"
                    :requiere-parcial="false"
                >
                    <div class="mt-4 flex flex-col gap-4 rounded-lg bg-white p-6 shadow-sm" x-show="seleccionCompleta && coincideConGuardado" x-cloak>
                        @if ($registro && $contexto)
                            <p class="text-sm leading-6 text-slate-600">
                                El comportamiento se califica con la tabla de equivalencias del periodo.
                                Esta calificación no modifica el promedio de las asignaturas.
                            </p>

                            <x-input-error :messages="$errors->get('comportamientos')" />

                            @if ($registro['escala'] === [])
                                <p class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    El periodo no tiene una tabla de comportamiento activa. El administrador la configura en Tablas de Equivalencias.
                                </p>
                            @elseif ($registro['alumnos'] === [])
                                <p class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                                    No hay alumnos matriculados en este aula.
                                </p>
                            @else
                                <form method="POST" action="{{ route('Docente.comportamiento.registrar') }}" class="flex flex-col gap-4">
                                    @csrf
                                    <input type="hidden" name="establecimiento_aula_id" value="{{ $contexto->aula->id }}">
                                    <input type="hidden" name="establecimiento_periodo_ciclo_id" value="{{ $contexto->ciclo->id }}">

                                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead class="bg-slate-50">
                                                <tr>
                                                    <th scope="col" class="w-12 px-4 py-3 text-left font-semibold text-slate-700">Nº</th>
                                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700">Estudiante</th>
                                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700">Calificación</th>
                                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700">Observación</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach ($registro['alumnos'] as $indice => $alumno)
                                                    @php
                                                        $calificacionActual = old('comportamientos.'.$alumno['id'].'.calificacion', $alumno['calificacion']);
                                                        $observacionActual = old('comportamientos.'.$alumno['id'].'.observacion', $alumno['observacion']);
                                                    @endphp
                                                    <tr x-data="{ calificacion: @js($calificacionActual ?? ''), exige: @js($registro['exige']) }">
                                                        <td class="px-4 py-2 text-slate-500">{{ $indice + 1 }}</td>
                                                        <td class="px-4 py-2 text-slate-800">{{ $alumno['nombre'] }}</td>
                                                        <td class="px-4 py-2">
                                                            <label class="sr-only" for="calificacion-{{ $alumno['id'] }}">Calificación de {{ $alumno['nombre'] }}</label>
                                                            <select
                                                                id="calificacion-{{ $alumno['id'] }}"
                                                                name="comportamientos[{{ $alumno['id'] }}][calificacion]"
                                                                x-model="calificacion"
                                                                class="block w-full min-w-56 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                                required
                                                            >
                                                                <option value="">Calificación</option>
                                                                @foreach ($registro['escala'] as $escala)
                                                                    <option value="{{ $escala['id'] }}">{{ $escala['valor'] }} — {{ $escala['nombre'] }}</option>
                                                                @endforeach
                                                            </select>
                                                            <x-input-error class="mt-1" :messages="$errors->get('comportamientos.'.$alumno['id'].'.calificacion')" />
                                                        </td>
                                                        <td class="px-4 py-2">
                                                            <div x-show="exige.includes(String(calificacion))" x-cloak>
                                                                <label class="sr-only" for="observacion-{{ $alumno['id'] }}">Observación de {{ $alumno['nombre'] }}</label>
                                                                <textarea
                                                                    id="observacion-{{ $alumno['id'] }}"
                                                                    name="comportamientos[{{ $alumno['id'] }}][observacion]"
                                                                    rows="2"
                                                                    maxlength="500"
                                                                    class="block w-full min-w-64 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                                >{{ $observacionActual }}</textarea>
                                                                <x-input-error class="mt-1" :messages="$errors->get('comportamientos.'.$alumno['id'].'.observacion')" />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <ul class="flex flex-col gap-1 text-xs leading-5 text-slate-500">
                                        @foreach ($registro['escala'] as $escala)
                                            <li><span class="font-semibold text-slate-700">{{ $escala['valor'] }}.</span> {{ $escala['descripcion'] }}</li>
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
